<?php

namespace App\Console\Commands;

use App\Jobs\ProcessTextFilterArchiveUpload;
use App\Services\FilterImportDecision;
use App\Services\FilterImportGate;
use App\Services\FilterImportOutcome;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class DiscoverFilterImports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'filter:discover {--dry-run : Report what would be imported without dispatching jobs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Discover changed filter exports and queue their imports';

    /**
     * @var array
     */
    private $outcomeCounts = [];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->outcomeCounts = array_fill_keys($this->outcomeNames(), 0);

        $dryRun = (bool) $this->option('dry-run');
        $objectsSeen = 0;
        $jobsDispatched = 0;
        $dispatchErrors = 0;
        $gateErrors = 0;
        $listingErrors = 0;

        $this->line($dryRun
            ? 'Filter import discovery started (dry-run; no jobs will be dispatched).'
            : 'Filter import discovery started.');

        try {
            $disk = Storage::disk(FilterImportGate::EXPORT_DISK);
            $gate = app()->bound(FilterImportGate::class)
                ? app()->make(FilterImportGate::class)
                : app()->makeWith(FilterImportGate::class, ['exportDisk' => $disk]);
            $objects = $disk->listContents('', false);

            if (!is_array($objects)) {
                throw new \RuntimeException('The export disk returned an invalid contents listing.');
            }

            foreach ($objects as $object) {
                if (is_array($object) && isset($object['type']) && $object['type'] !== 'file') {
                    continue;
                }

                if (!is_array($object)) {
                    $this->reportListingError(
                        null,
                        'The export disk returned a non-array listing entry.',
                        $listingErrors,
                        $gate
                    );

                    continue;
                }

                $objectsSeen++;
                $objectKey = isset($object['path']) ? $object['path'] : null;

                if (!is_string($objectKey) || $objectKey === '') {
                    $this->reportListingError(
                        null,
                        'The listing entry has no object path.',
                        $listingErrors,
                        $gate
                    );

                    continue;
                }

                // Flysystem v1 includes the S3 LastModified value as a Unix timestamp
                // in each plain-array listing entry. Passing it avoids a HEAD request.
                if (!array_key_exists('timestamp', $object) || !is_numeric($object['timestamp'])) {
                    $this->reportListingError(
                        $objectKey,
                        'The listing entry has no usable LastModified timestamp.',
                        $listingErrors,
                        $gate
                    );

                    continue;
                }

                try {
                    $decision = $gate->decide($objectKey, (int) $object['timestamp']);
                } catch (Throwable $exception) {
                    $gateErrors++;
                    $this->incrementOutcome(FilterImportOutcome::DISK_ERROR);
                    $category = $gate->categoryFromObjectKey($objectKey);

                    $this->error(sprintf(
                        '[%s] category=%s object=%s - gate error: %s',
                        FilterImportOutcome::DISK_ERROR,
                        $category === null ? '(unknown)' : $category,
                        $objectKey,
                        $exception->getMessage()
                    ));

                    continue;
                }

                // Names such as export.zip are intentionally excluded by the gate
                // before they can become import candidates.
                if ($decision === null) {
                    continue;
                }

                $this->incrementOutcome($decision->outcome);
                $detail = $this->formatDecision($decision, $objectKey);

                if (!$decision->shouldImport()) {
                    $this->line($detail);

                    continue;
                }

                if ($decision->category === null) {
                    $dispatchErrors++;
                    $this->error($detail.' - dispatch-error: the import decision has no category');

                    continue;
                }

                if ($dryRun) {
                    $this->line($detail.' - would dispatch (dry-run)');

                    continue;
                }

                try {
                    ProcessTextFilterArchiveUpload::dispatch(
                        FilterImportGate::DEFAULT_NAMESPACE,
                        $objectKey,
                        true,
                        $decision->category,
                        FilterImportGate::EXPORT_DISK
                    );
                    $jobsDispatched++;
                    $this->line($detail.' - dispatched');
                } catch (Throwable $exception) {
                    $dispatchErrors++;
                    $this->error($detail.' - dispatch-error: '.$exception->getMessage());
                }
            }
        } catch (Throwable $exception) {
            $listingErrors++;
            $this->incrementOutcome(FilterImportOutcome::DISK_ERROR);
            $this->error(
                '['.FilterImportOutcome::DISK_ERROR.'] category=(listing) object=export disk - Unable to list the export disk: '
                .$exception->getMessage()
            );
            $this->renderSummary(
                $objectsSeen,
                $jobsDispatched,
                $dispatchErrors,
                $gateErrors,
                $listingErrors
            );

            return 1;
        }

        $this->renderSummary(
            $objectsSeen,
            $jobsDispatched,
            $dispatchErrors,
            $gateErrors,
            $listingErrors
        );

        return $dispatchErrors > 0 || $gateErrors > 0 || $listingErrors > 0
            ? 1
            : 0;
    }

    /**
     * @param FilterImportDecision $decision
     * @param string $fallbackObjectKey
     * @return string
     */
    private function formatDecision(FilterImportDecision $decision, $fallbackObjectKey)
    {
        return sprintf(
            '[%s] category=%s object=%s%s',
            $decision->outcome,
            $decision->category === null ? '(unknown)' : $decision->category,
            $decision->objectKey === null ? $fallbackObjectKey : $decision->objectKey,
            $decision->reason === null ? '' : ' - '.$decision->reason
        );
    }

    /**
     * @param string|null $objectKey
     * @param string $reason
     * @param int $listingErrors
     * @param FilterImportGate $gate
     * @return void
     */
    private function reportListingError($objectKey, $reason, &$listingErrors, FilterImportGate $gate)
    {
        $listingErrors++;
        $this->incrementOutcome(FilterImportOutcome::DISK_ERROR);

        $category = $objectKey === null ? null : $gate->categoryFromObjectKey($objectKey);
        $this->error(sprintf(
            '[%s] category=%s object=%s - %s',
            FilterImportOutcome::DISK_ERROR,
            $category === null ? '(unknown)' : $category,
            $objectKey === null ? '(listing entry)' : $objectKey,
            $reason
        ));
    }

    /**
     * @param string $outcome
     * @return void
     */
    private function incrementOutcome($outcome)
    {
        if (!array_key_exists($outcome, $this->outcomeCounts)) {
            $this->outcomeCounts[$outcome] = 0;
        }

        $this->outcomeCounts[$outcome]++;
    }

    /**
     * @return array
     */
    private function outcomeNames()
    {
        return [
            FilterImportOutcome::IMPORTED,
            FilterImportOutcome::SKIPPED_ALREADY_CURRENT,
            FilterImportOutcome::NOT_IN_ALLOWLIST,
            FilterImportOutcome::DENIED,
            FilterImportOutcome::OBJECT_MISSING,
            FilterImportOutcome::DISK_ERROR,
        ];
    }

    /**
     * @param int $objectsSeen
     * @param int $jobsDispatched
     * @param int $dispatchErrors
     * @param int $gateErrors
     * @param int $listingErrors
     * @return void
     */
    private function renderSummary(
        $objectsSeen,
        $jobsDispatched,
        $dispatchErrors,
        $gateErrors,
        $listingErrors
    ) {
        $this->line('');
        $this->line('Filter import outcome summary:');

        foreach ($this->outcomeNames() as $outcome) {
            $this->line(sprintf('  %s: %d', $outcome, $this->outcomeCounts[$outcome]));
        }

        $this->line('  objects-seen: '.$objectsSeen);
        $this->line('  jobs-dispatched: '.$jobsDispatched);
        $this->line('  dispatch-errors: '.$dispatchErrors);
        $this->line('  gate-errors: '.$gateErrors);
        $this->line('  listing-errors: '.$listingErrors);
    }
}
