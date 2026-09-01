<?php

namespace App\Console\Commands;

use App\Jobs\ProcessTextFilterArchiveUpload;
use App\Services\FilterImportGate;
use App\Services\FilterImportOutcome;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class FilterImportExport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'filter:import-export {category} {--force : Re-import when local rule files are current}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import one category from the export bucket';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $category = (string) $this->argument('category');
        $objectKey = 'export_'.$category.'.zip';

        try {
            // Pass the configured export disk explicitly. This prevents the
            // container from resolving a default local filesystem instead.
            $gate = app()->bound(FilterImportGate::class)
                ? app()->make(FilterImportGate::class)
                : app()->makeWith(FilterImportGate::class, [
                    'exportDisk' => Storage::disk(FilterImportGate::EXPORT_DISK),
                ]);
            $decision = $gate->decide($objectKey);
        } catch (Throwable $exception) {
            $this->line('Category: '.$category);
            $this->line('Object: '.$objectKey);
            $this->line('Outcome: '.FilterImportOutcome::DISK_ERROR);
            $this->line('Reason: Unable to decide whether the export can be imported: '.$exception->getMessage());

            return 1;
        }

        if ($decision === null) {
            $this->line('Category: '.$category);
            $this->line('Object: '.$objectKey);
            $this->line('Outcome: '.FilterImportOutcome::DISK_ERROR);
            $this->line('Reason: Unable to derive a category from the object key.');

            return 1;
        }

        $forceCurrentImport = (bool) $this->option('force')
            && $decision->outcome === FilterImportOutcome::SKIPPED_ALREADY_CURRENT;

        $this->line('Category: '.$category);
        $this->line('Object: '.$objectKey);
        $this->line('Outcome: '.$decision->outcome);

        if ($decision->reason !== null) {
            $this->line('Reason: '.$decision->reason);
        }

        if (!$decision->shouldImport() && !$forceCurrentImport) {
            return in_array($decision->outcome, [
                FilterImportOutcome::DISK_ERROR,
                FilterImportOutcome::OBJECT_MISSING,
            ], true) ? 1 : 0;
        }

        if ($forceCurrentImport) {
            $this->line('Force enabled: bypassing the modified-time check only.');
        }

        try {
            ProcessTextFilterArchiveUpload::dispatch(
                FilterImportGate::DEFAULT_NAMESPACE,
                $objectKey,
                true,
                $category,
                FilterImportGate::EXPORT_DISK
            );
        } catch (Throwable $exception) {
            $this->error('Import dispatch failed: '.$exception->getMessage());

            return 1;
        }

        $this->info('Import job dispatched.');

        return 0;
    }
}
