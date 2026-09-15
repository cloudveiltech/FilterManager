<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class BackfillWordPressIdentity extends Command
{
    private const CHUNK_SIZE = 500;

    private const OUTCOMES = [
        'ok',
        'set_from_customer_id',
        'set_from_email',
        'review',
        'no_match',
    ];

    protected $signature = 'users:backfill-wordpress-identity
                            {csv : CSV containing wordpress_user_id and email columns}
                            {--apply : Apply identity updates; otherwise run as a dry run}
                            {--report= : Path for the per-user CSV report}';

    protected $description = 'Backfill CloudVeilManager users with WordPress identities';

    public function handle(): int
    {
        try {
            $identities = $this->readIdentities((string) $this->argument('csv'));
            $reportPath = $this->resolveReportPath();
            $this->ensureReportDirectory($reportPath);

            $report = fopen($reportPath, 'wb');

            if ($report === false) {
                throw new RuntimeException('Unable to open the report path for writing: '.$reportPath);
            }

            $counts = array_fill_keys(self::OUTCOMES, 0);
            $usersProcessed = 0;
            $reservedProviderIds = [];
            $appliedProviderIds = [];
            $heldProviderIds = $this->heldProviderIds();
            $apply = (bool) $this->option('apply');

            try {
                $this->writeReportRow($report, [
                    'user_id',
                    'email',
                    'customer_id',
                    'provider_id',
                    'proposed_provider_id',
                    'outcome',
                    'reason',
                ]);

                DB::table('users')
                    ->select(['id', 'email', 'customer_id', 'provider', 'provider_id'])
                    ->orderBy('id')
                    ->chunkById(self::CHUNK_SIZE, function ($users) use (
                        $identities,
                        $apply,
                        $report,
                        &$counts,
                        &$usersProcessed,
                        &$reservedProviderIds,
                        &$appliedProviderIds,
                        $heldProviderIds,
                    ): void {
                        $records = [];

                        foreach ($users as $user) {
                            $records[] = $this->planUser(
                                $user,
                                $identities,
                                $heldProviderIds,
                                $reservedProviderIds,
                            );
                        }

                        if ($apply) {
                            $this->applyRecords($records, $appliedProviderIds);
                        }

                        foreach ($records as $record) {
                            $counts[$record['outcome']]++;
                            $usersProcessed++;

                            $this->writeReportRow($report, [
                                $record['user_id'],
                                $record['email'],
                                $record['customer_id'],
                                $record['provider_id'],
                                $record['proposed_provider_id'],
                                $record['outcome'],
                                $record['reason'],
                            ]);
                        }
                    });
            } finally {
                fclose($report);
            }

            $this->renderSummary(
                $counts,
                $usersProcessed,
                $identities['invalid_rows'],
                $reportPath,
                $apply,
            );

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * @return array{
     *     emails_by_id: array<int, array<int, string>>,
     *     ids_by_email: array<string, array<int, int>>,
     *     invalid_rows: int,
     * }
     */
    private function readIdentities(string $path): array
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('Unable to open the input CSV: '.$path);
        }

        try {
            $header = fgetcsv($handle);

            if ($header === false) {
                throw new RuntimeException('The input CSV is empty.');
            }

            $header = array_map(fn ($value): string => $this->normalizeHeaderValue($value), $header);
            $idColumn = array_search('wordpress_user_id', $header, true);
            $emailColumn = array_search('email', $header, true);

            if ($idColumn === false || $emailColumn === false) {
                throw new RuntimeException('The input CSV must contain wordpress_user_id and email columns.');
            }

            $emailsById = [];
            $idsByEmail = [];
            $invalidRows = 0;

            while (($row = fgetcsv($handle)) !== false) {
                if ($this->isBlankRow($row)) {
                    continue;
                }

                $rawId = trim((string) ($row[$idColumn] ?? ''));
                $email = $this->normalizeEmail($row[$emailColumn] ?? '');

                if (! ctype_digit($rawId) || (int) $rawId < 1 || filter_var($rawId, FILTER_VALIDATE_INT) === false || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $invalidRows++;

                    continue;
                }

                $wordpressUserId = (int) $rawId;
                $emailsById[$wordpressUserId][$email] = true;
                $idsByEmail[$email][$wordpressUserId] = true;
            }

            return [
                'emails_by_id' => array_map(
                    static fn (array $emails): array => array_keys($emails),
                    $emailsById,
                ),
                'ids_by_email' => array_map(
                    static fn (array $ids): array => array_map('intval', array_keys($ids)),
                    $idsByEmail,
                ),
                'invalid_rows' => $invalidRows,
            ];
        } finally {
            fclose($handle);
        }
    }

    private function normalizeHeaderValue(mixed $value): string
    {
        $value = trim((string) $value);
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;

        return strtolower($value);
    }

    private function normalizeEmail(mixed $email): string
    {
        return strtolower(trim((string) $email));
    }

    private function isBlankRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<int, array<int, int>>
     */
    private function heldProviderIds(): array
    {
        $heldProviderIds = [];

        DB::table('users')
            ->whereNotNull('provider_id')
            ->select(['id', 'provider_id'])
            ->orderBy('id')
            ->get()
            ->each(function ($user) use (&$heldProviderIds): void {
                $providerId = $this->positiveInteger($user->provider_id);

                if ($providerId === null) {
                    return;
                }

                $heldProviderIds[$providerId][] = (int) $user->id;
            });

        return $heldProviderIds;
    }

    /**
     * @param array{
     *     emails_by_id: array<int, array<int, string>>,
     *     ids_by_email: array<string, array<int, int>>,
     *     invalid_rows: int,
     * } $identities
     * @param array<int, array<int, int>> $heldProviderIds
     * @param array<int, int> $reservedProviderIds
     * @return array<string, int|string|null>
     */
    private function planUser(
        object $user,
        array $identities,
        array $heldProviderIds,
        array &$reservedProviderIds,
    ): array {
        $userId = (int) $user->id;
        $email = $this->normalizeEmail($user->email ?? '');
        $providerId = $user->provider_id;
        $customerId = $this->positiveInteger($user->customer_id);

        $baseRecord = [
            'user_id' => $userId,
            'email' => $user->email,
            'customer_id' => $user->customer_id,
            'provider_id' => $providerId,
            'proposed_provider_id' => null,
        ];

        if ($this->providerIdIsSet($providerId)) {
            $existingProviderId = $this->positiveInteger($providerId);

            if ($user->provider === 'cloudveil' && $existingProviderId !== null) {
                $mappedEmails = $identities['emails_by_id'][$existingProviderId] ?? [];

                if (count($mappedEmails) === 1 && $mappedEmails[0] === $email) {
                    return $baseRecord + [
                        'outcome' => 'ok',
                        'reason' => 'provider_id maps to this email in the WordPress CSV',
                    ];
                }

                return $baseRecord + [
                    'outcome' => 'review',
                    'reason' => count($mappedEmails) > 1
                        ? 'provider_id maps to multiple emails in the WordPress CSV'
                        : 'provider_id does not map to this email in the WordPress CSV',
                ];
            }

            return $baseRecord + [
                'outcome' => 'review',
                'reason' => 'provider_id is already set without a cloudveil provider identity',
            ];
        }

        if ($customerId !== null) {
            $mappedEmails = $identities['emails_by_id'][$customerId] ?? [];

            if (count($mappedEmails) === 1 && $mappedEmails[0] === $email) {
                $mappedIds = $identities['ids_by_email'][$email] ?? [];

                if (count($mappedIds) === 1 && $mappedIds[0] === $customerId) {
                    return $this->proposedRecord(
                        $baseRecord,
                        $customerId,
                        'set_from_customer_id',
                        'customer_id matches the WordPress id for this email',
                        $userId,
                        $heldProviderIds,
                        $reservedProviderIds,
                    );
                }

                return $baseRecord + [
                    'outcome' => 'review',
                    'reason' => 'email maps to more than the customer_id in the WordPress CSV',
                ];
            }

            if (count($mappedEmails) > 0) {
                return $baseRecord + [
                    'outcome' => 'review',
                    'reason' => 'customer_id maps to a different or ambiguous email in the WordPress CSV',
                ];
            }

            $mappedIds = $identities['ids_by_email'][$email] ?? [];

            if (count($mappedIds) > 0) {
                return $baseRecord + [
                    'outcome' => 'review',
                    'reason' => 'email maps to a WordPress id different from customer_id',
                ];
            }

            return $baseRecord + [
                'outcome' => 'no_match',
                'reason' => 'customer_id and email have no WordPress match',
            ];
        }

        $mappedIds = $identities['ids_by_email'][$email] ?? [];

        if (count($mappedIds) === 1) {
            return $this->proposedRecord(
                $baseRecord,
                $mappedIds[0],
                'set_from_email',
                'email matches exactly one WordPress id',
                $userId,
                $heldProviderIds,
                $reservedProviderIds,
            );
        }

        if (count($mappedIds) > 1) {
            return $baseRecord + [
                'outcome' => 'review',
                'reason' => 'email maps to multiple WordPress ids in the CSV',
            ];
        }

        return $baseRecord + [
            'outcome' => 'no_match',
            'reason' => 'email has no WordPress match',
        ];
    }

    /**
     * @param array<string, int|string|null> $baseRecord
     * @param array<int, array<int, int>> $heldProviderIds
     * @param array<int, int> $reservedProviderIds
     * @return array<string, int|string|null>
     */
    private function proposedRecord(
        array $baseRecord,
        int $proposedProviderId,
        string $outcome,
        string $reason,
        int $userId,
        array $heldProviderIds,
        array &$reservedProviderIds,
    ): array {
        $baseRecord['proposed_provider_id'] = $proposedProviderId;

        if ($this->hasOtherHolder($heldProviderIds[$proposedProviderId] ?? [], $userId)) {
            return $baseRecord + [
                'outcome' => 'review',
                'reason' => 'proposed provider_id is already held by another user',
            ];
        }

        if (isset($reservedProviderIds[$proposedProviderId]) && $reservedProviderIds[$proposedProviderId] !== $userId) {
            return $baseRecord + [
                'outcome' => 'review',
                'reason' => 'proposed provider_id is assigned to another user in this run',
            ];
        }

        $reservedProviderIds[$proposedProviderId] = $userId;

        return $baseRecord + [
            'outcome' => $outcome,
            'reason' => $reason,
        ];
    }

    /**
     * @param array<int, array<string, int|string|null>> $records
     * @param array<int, int> $appliedProviderIds
     */
    private function applyRecords(array &$records, array &$appliedProviderIds): void
    {
        $hasUpdates = false;

        foreach ($records as $record) {
            if (in_array($record['outcome'], ['set_from_customer_id', 'set_from_email'], true)) {
                $hasUpdates = true;

                break;
            }
        }

        if (! $hasUpdates) {
            return;
        }

        DB::transaction(function () use (&$records, &$appliedProviderIds): void {
            foreach ($records as &$record) {
                if (! in_array($record['outcome'], ['set_from_customer_id', 'set_from_email'], true)) {
                    continue;
                }

                $userId = (int) $record['user_id'];
                $proposedProviderId = (int) $record['proposed_provider_id'];

                if (isset($appliedProviderIds[$proposedProviderId]) && $appliedProviderIds[$proposedProviderId] !== $userId) {
                    $this->markReview($record, 'proposed provider_id was assigned to another user earlier in this run');

                    continue;
                }

                $currentUser = DB::table('users')
                    ->where('id', $userId)
                    ->lockForUpdate()
                    ->first(['provider', 'provider_id']);

                if ($currentUser === null) {
                    $this->markReview($record, 'user no longer exists at write time');

                    continue;
                }

                if ($this->providerIdIsSet($currentUser->provider_id)) {
                    $this->markReview($record, 'user already has a provider_id at write time');

                    continue;
                }

                $holder = DB::table('users')
                    ->where('provider_id', $proposedProviderId)
                    ->where('id', '<>', $userId)
                    ->lockForUpdate()
                    ->first(['id']);

                if ($holder !== null) {
                    $this->markReview($record, 'proposed provider_id is already held by another user at write time');

                    continue;
                }

                $updated = DB::table('users')
                    ->where('id', $userId)
                    ->whereNull('provider_id')
                    ->update([
                        'provider' => 'cloudveil',
                        'provider_id' => $proposedProviderId,
                    ]);

                if ($updated !== 1) {
                    $this->markReview($record, 'user could not be updated at write time');

                    continue;
                }

                $appliedProviderIds[$proposedProviderId] = $userId;
            }

            unset($record);
        });
    }

    /**
     * @param array<string, int|string|null> $record
     */
    private function markReview(array &$record, string $reason): void
    {
        $record['outcome'] = 'review';
        $record['reason'] = $reason;
    }

    /**
     * @param array<int, int> $holderIds
     */
    private function hasOtherHolder(array $holderIds, int $userId): bool
    {
        foreach ($holderIds as $holderId) {
            if ($holderId !== $userId) {
                return true;
            }
        }

        return false;
    }

    private function providerIdIsSet(mixed $providerId): bool
    {
        return $providerId !== null && trim((string) $providerId) !== '';
    }

    private function positiveInteger(mixed $value): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $value = trim((string) $value);

        if (! ctype_digit($value) || (int) $value < 1 || filter_var($value, FILTER_VALIDATE_INT) === false) {
            return null;
        }

        return (int) $value;
    }

    private function resolveReportPath(): string
    {
        $reportOption = trim((string) ($this->option('report') ?? ''));

        if ($reportOption === '') {
            return storage_path('app/wordpress-identity-backfill-'.date('Ymd-His').'.csv');
        }

        if ($reportOption[0] === DIRECTORY_SEPARATOR) {
            return $reportOption;
        }

        return base_path($reportOption);
    }

    private function ensureReportDirectory(string $reportPath): void
    {
        $directory = dirname($reportPath);

        if ($directory === '.' || is_dir($directory)) {
            return;
        }

        if (! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('Unable to create the report directory: '.$directory);
        }
    }

    /**
     * @param resource $report
     * @param array<int, mixed> $row
     */
    private function writeReportRow($report, array $row): void
    {
        if (fputcsv($report, $row) === false) {
            throw new RuntimeException('Unable to write the report CSV.');
        }
    }

    /**
     * @param array<string, int> $counts
     */
    private function renderSummary(
        array $counts,
        int $usersProcessed,
        int $invalidRows,
        string $reportPath,
        bool $apply,
    ): void {
        $this->line($apply ? 'WordPress identity backfill applied.' : 'WordPress identity backfill dry run; no database writes performed.');
        $this->line('Users processed: '.$usersProcessed);
        $this->line('Skipped invalid CSV rows: '.$invalidRows);
        $this->line('Outcome counts:');

        foreach (self::OUTCOMES as $outcome) {
            $this->line(sprintf('  %s: %d', $outcome, $counts[$outcome]));
        }

        $this->line('Report: '.$reportPath);
    }
}
