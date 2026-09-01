<?php

namespace App\Console\Commands;

use App\Jobs\ProcessTextFilterArchiveUpload;
use App\Services\FilterImportDecision;
use App\Services\FilterImportGate;
use App\Services\FilterImportOutcome;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\StorageAttributes;
use Throwable;

class DiscoverFilterImports extends Command
{
    protected $signature = 'filter:discover
                            {--dry-run : Report what would be imported without dispatching jobs}';

    protected $description = 'Discover changed filter exports and queue their imports';

    /**
     * @var array<string, int>
     */
    private array $outcomeCounts = [];

    public function handle(FilterImportGate $gate): int
    {
        $this->outcomeCounts = array_fill_keys(
            array_map(static fn (FilterImportOutcome $outcome): string => $outcome->value, FilterImportOutcome::cases()),
            0,
        );

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
            $objects = Storage::disk(FilterImportGate::EXPORT_DISK)->listContents('', false);

            foreach ($objects as $object) {
                if (! $object instanceof StorageAttributes || ! $object->isFile()) {
                    continue;
                }

                $objectsSeen++;
                $objectKey = $object->path();

                try {
                    // The listing already contains LastModified. Passing it avoids a metadata
                    // request for every object when the gate makes its decision.
                    $decision = $gate->decide($objectKey, $object->lastModified());
                } catch (Throwable $exception) {
                    $gateErrors++;
                    $category = $gate->categoryFromObjectKey($objectKey);

                    if ($category !== null) {
                        $this->error(sprintf(
                            '[gate-error] category=%s object=%s — %s',
                            $category,
                            $objectKey,
                            $exception->getMessage(),
                        ));
                    }

                    continue;
                }

                // Non-category objects such as export.zip are intentionally invisible to
                // operators: they are not candidates and are not errors.
                if ($decision === null) {
                    continue;
                }

                $this->outcomeCounts[$decision->outcome->value]++;
                $detail = $this->formatDecision($decision, $objectKey);

                if (! $decision->shouldImport()) {
                    $this->line($detail);

                    continue;
                }

                if ($decision->category === null) {
                    $dispatchErrors++;
                    $this->error($detail.' — dispatch-error: the import decision has no category');

                    continue;
                }

                if ($dryRun) {
                    $this->line($detail.' — would dispatch (dry-run)');

                    continue;
                }

                try {
                    ProcessTextFilterArchiveUpload::dispatch(
                        FilterImportGate::DEFAULT_NAMESPACE,
                        $objectKey,
                        true,
                        $decision->category,
                        FilterImportGate::EXPORT_DISK,
                    );
                    $jobsDispatched++;
                    $this->line($detail.' — dispatched');
                } catch (Throwable $exception) {
                    $dispatchErrors++;
                    $this->error($detail.' — dispatch-error: '.$exception->getMessage());
                }
            }
        } catch (Throwable $exception) {
            $listingErrors++;
            $this->outcomeCounts[FilterImportOutcome::DISK_ERROR->value]++;
            $this->error('[disk-error] Unable to list the export disk: '.$exception->getMessage());
            $this->renderSummary($objectsSeen, $jobsDispatched, $dispatchErrors, $gateErrors, $listingErrors);

            return self::FAILURE;
        }

        $this->renderSummary($objectsSeen, $jobsDispatched, $dispatchErrors, $gateErrors, $listingErrors);

        return $dispatchErrors > 0 || $gateErrors > 0 || $listingErrors > 0
            ? self::FAILURE
            : self::SUCCESS;
    }

    private function formatDecision(FilterImportDecision $decision, string $fallbackObjectKey): string
    {
        return sprintf(
            '[%s] category=%s object=%s%s',
            $decision->outcome->value,
            $decision->category ?? '(unknown)',
            $decision->objectKey ?? $fallbackObjectKey,
            $decision->reason === null ? '' : ' — '.$decision->reason,
        );
    }

    private function renderSummary(
        int $objectsSeen,
        int $jobsDispatched,
        int $dispatchErrors,
        int $gateErrors,
        int $listingErrors,
    ): void {
        $this->newLine();
        $this->line('Filter import outcome summary:');

        foreach (FilterImportOutcome::cases() as $outcome) {
            $this->line(sprintf('  %s: %d', $outcome->value, $this->outcomeCounts[$outcome->value]));
        }

        $this->line('  objects-seen: '.$objectsSeen);
        $this->line('  jobs-dispatched: '.$jobsDispatched);
        $this->line('  dispatch-errors: '.$dispatchErrors);
        $this->line('  gate-errors: '.$gateErrors);
        $this->line('  listing-errors: '.$listingErrors);
    }
}
