<?php

namespace App\Services;

use App\Models\FilterList;
use App\Models\FilterRulesManager;
use Closure;
use DateTimeInterface;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class FilterImportGate
{
    public const DEFAULT_NAMESPACE = 'default';

    public const EXPORT_DISK = 'export';

    public const DEFAULT_CLOCK_TOLERANCE_SECONDS = 5;

    public const DISK_ERROR_REASON = 'The export object or the local rule files could not be read.';

    private readonly Filesystem $exportDisk;

    private readonly FilterRulesManager $rulesManager;

    /** @var list<string> */
    private readonly array $deniedCategories;

    private readonly int $clockToleranceSeconds;

    /** @var Closure(string, string): bool */
    private readonly Closure $hasFilterLists;

    /**
     * @param  list<string>|null  $deniedCategories
     * @param  Closure(string, string): bool|null  $hasFilterLists
     */
    public function __construct(
        ?Filesystem $exportDisk = null,
        ?FilterRulesManager $rulesManager = null,
        ?array $deniedCategories = null,
        ?int $clockToleranceSeconds = null,
        ?Closure $hasFilterLists = null,
    ) {
        $this->exportDisk = $exportDisk ?? Storage::disk(self::EXPORT_DISK);
        $this->rulesManager = $rulesManager ?? new FilterRulesManager;
        $this->deniedCategories = array_values($deniedCategories ?? config('filter_imports.deny', []));
        $this->clockToleranceSeconds = max(
            0,
            $clockToleranceSeconds ?? (int) config(
                'filter_imports.clock_tolerance_seconds',
                self::DEFAULT_CLOCK_TOLERANCE_SECONDS,
            ),
        );
        $this->hasFilterLists = $hasFilterLists ?? static function (string $namespace, string $category): bool {
            return FilterList::query()
                ->where('namespace', $namespace)
                ->where('category', $category)
                ->exists();
        };
    }

    /**
     * Extract a category only from an export_<category>.zip object name.
     *
     * A null return is intentional: names such as export.zip are not
     * categories and must never become candidates for the import path.
     */
    public function categoryFromObjectKey(string $objectKey): ?string
    {
        $filename = basename($objectKey);

        if (preg_match('/^export_(?<category>[^\/]+)\.zip$/D', $filename, $matches) !== 1) {
            return null;
        }

        return $matches['category'] !== '' ? $matches['category'] : null;
    }

    /**
     * Decide whether a bucket object should be handed to the importer.
     *
     * Pass the LastModified value from a bucket listing when it is already
     * available. If it is omitted, this method verifies the object exists and
     * reads its LastModified from the export disk. The nullable return is only
     * for structurally invalid/non-category object names.
     */
    public function decide(
        string $objectKey,
        int|DateTimeInterface|null $objectLastModified = null,
    ): ?FilterImportDecision {
        $category = $this->categoryFromObjectKey($objectKey);

        if ($category === null) {
            return null;
        }

        $resolvedLastModified = $this->resolveObjectLastModified($objectKey, $objectLastModified, $category);

        if ($resolvedLastModified instanceof FilterImportDecision) {
            return $resolvedLastModified;
        }

        if (in_array(strtolower($category), array_map('strtolower', $this->deniedCategories), true)) {
            return new FilterImportDecision(
                outcome: FilterImportOutcome::DENIED,
                objectKey: $objectKey,
                category: $category,
                reason: 'The category is present in filter_imports.deny.',
            );
        }

        try {
            $isAllowlisted = ($this->hasFilterLists)(self::DEFAULT_NAMESPACE, $category);
        } catch (Throwable $exception) {
            return $this->diskError(
                $objectKey,
                $category,
                'Unable to inspect the filter_lists allowlist: '.$exception->getMessage(),
            );
        }

        if (! $isAllowlisted) {
            return new FilterImportDecision(
                outcome: FilterImportOutcome::NOT_IN_ALLOWLIST,
                objectKey: $objectKey,
                category: $category,
                reason: 'No default filter_lists row exists for the category.',
            );
        }

        $localRules = $this->inspectLocalRules($category);

        if ($localRules['error'] !== null) {
            return $this->diskError(
                $objectKey,
                $category,
                $localRules['error'],
            );
        }

        if ($localRules['missing']) {
            return new FilterImportDecision(
                outcome: FilterImportOutcome::IMPORTED,
                objectKey: $objectKey,
                category: $category,
                reason: 'A local rule file is missing: '.$localRules['missingFilename'].'.',
            );
        }

        $newestLocalMtime = $localRules['newest'];

        if ($newestLocalMtime === null) {
            return $this->diskError(
                $objectKey,
                $category,
                'No rule file types are configured for the category.',
            );
        }

        if ($newestLocalMtime + $this->clockToleranceSeconds >= $resolvedLastModified) {
            return new FilterImportDecision(
                outcome: FilterImportOutcome::SKIPPED_ALREADY_CURRENT,
                objectKey: $objectKey,
                category: $category,
                reason: 'The newest local rule file is current within the clock tolerance.',
            );
        }

        return new FilterImportDecision(
            outcome: FilterImportOutcome::IMPORTED,
            objectKey: $objectKey,
            category: $category,
            reason: 'The bucket object is newer than the newest local rule file.',
        );
    }

    private function resolveObjectLastModified(
        string $objectKey,
        int|DateTimeInterface|null $objectLastModified,
        string $category,
    ): int|FilterImportDecision {
        if ($objectLastModified instanceof DateTimeInterface) {
            return $objectLastModified->getTimestamp();
        }

        if ($objectLastModified !== null) {
            return $objectLastModified;
        }

        try {
            if (! $this->exportDisk->exists($objectKey)) {
                return new FilterImportDecision(
                    outcome: FilterImportOutcome::OBJECT_MISSING,
                    objectKey: $objectKey,
                    category: $category,
                    reason: 'The bucket object does not exist on the export disk.',
                );
            }

            return $this->exportDisk->lastModified($objectKey);
        } catch (Throwable $exception) {
            return $this->diskError(
                $objectKey,
                $category,
                'Unable to read the bucket object metadata: '.$exception->getMessage(),
            );
        }
    }

    /**
     * @return array{missing: bool, missingFilename: ?string, newest: ?int, error: ?string}
     */
    private function inspectLocalRules(string $category): array
    {
        $newest = null;
        $missingFilename = null;

        foreach (FilterRulesManager::TYPES as $mappedType) {
            try {
                set_error_handler(static function (
                    int $severity,
                    string $message,
                    string $file,
                    int $line,
                ): never {
                    throw new \ErrorException($message, 0, $severity, $file, $line);
                });

                try {
                    $filename = $this->rulesManager->getFilename(
                        self::DEFAULT_NAMESPACE,
                        $category,
                        $mappedType.'.txt',
                    );
                    $path = $this->rulesManager->getRulesetPath(
                        self::DEFAULT_NAMESPACE,
                        $category,
                        $mappedType,
                    );
                } finally {
                    restore_error_handler();
                }
            } catch (Throwable $exception) {
                return [
                    'missing' => false,
                    'missingFilename' => null,
                    'newest' => $newest,
                    'error' => 'Unable to resolve the local rule file path: '.$exception->getMessage(),
                ];
            }

            if (! is_string($filename) || ! is_string($path) || $path === '') {
                return [
                    'missing' => false,
                    'missingFilename' => null,
                    'newest' => $newest,
                    'error' => 'The local rule path resolver returned an invalid path.',
                ];
            }

            clearstatcache(true, $path);

            if (! @file_exists($path)) {
                $directory = dirname($path);

                if (@file_exists($directory) && ! @is_readable($directory)) {
                    return [
                        'missing' => false,
                        'missingFilename' => null,
                        'newest' => $newest,
                        'error' => 'The local rule directory is not readable: '.$directory,
                    ];
                }

                $missingFilename ??= $filename;

                continue;
            }

            if (! @is_file($path)) {
                return [
                    'missing' => false,
                    'missingFilename' => null,
                    'newest' => $newest,
                    'error' => 'The local rule path is not a regular file: '.$path,
                ];
            }

            $mtime = @filemtime($path);

            if ($mtime === false) {
                return [
                    'missing' => false,
                    'missingFilename' => null,
                    'newest' => $newest,
                    'error' => 'Unable to read the local rule file mtime: '.$path,
                ];
            }

            $newest = $newest === null ? $mtime : max($newest, $mtime);
        }

        return [
            'missing' => count(FilterRulesManager::TYPES) > 0 && $newest === null,
            'missingFilename' => $missingFilename,
            'newest' => $newest,
            'error' => null,
        ];
    }

    private function diskError(
        string $objectKey,
        string $category,
        string $detail,
    ): FilterImportDecision {
        // Operators get one reason; the specific failure goes to the log so a
        // broken sweep is still diagnosable.
        Log::warning('Filter import disk error', [
            'object' => $objectKey,
            'category' => $category,
            'detail' => $detail,
        ]);

        return new FilterImportDecision(
            outcome: FilterImportOutcome::DISK_ERROR,
            objectKey: $objectKey,
            category: $category,
            reason: self::DISK_ERROR_REASON,
        );
    }
}
