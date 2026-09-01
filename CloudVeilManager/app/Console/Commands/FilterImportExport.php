<?php

namespace App\Console\Commands;

use App\Jobs\ProcessTextFilterArchiveUpload;
use App\Services\FilterImportGate;
use App\Services\FilterImportOutcome;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

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
        $gate = app()->makeWith(FilterImportGate::class, [
            'exportDisk' => Storage::disk(FilterImportGate::EXPORT_DISK),
        ]);
        $category = (string) $this->argument('category');
        $objectKey = 'export_'.$category.'.zip';
        $decision = $gate->decide($objectKey);

        if ($decision === null) {
            $this->error("Unable to derive a category from object key [{$objectKey}].");

            return self::FAILURE;
        }

        $forceCurrentImport = (bool) $this->option('force')
            && $decision->outcome === FilterImportOutcome::SKIPPED_ALREADY_CURRENT;

        $this->line('Category: '.$category);
        $this->line('Object: '.$objectKey);
        $this->line('Outcome: '.$decision->outcome->value);

        if ($decision->reason !== null) {
            $this->line('Reason: '.$decision->reason);
        }

        if (! $decision->shouldImport() && ! $forceCurrentImport) {
            return in_array($decision->outcome, [
                FilterImportOutcome::DISK_ERROR,
                FilterImportOutcome::OBJECT_MISSING,
            ], true) ? self::FAILURE : self::SUCCESS;
        }

        if ($forceCurrentImport) {
            $this->line('Force enabled: bypassing the modified-time check only.');
        }

        ProcessTextFilterArchiveUpload::dispatch(
            FilterImportGate::DEFAULT_NAMESPACE,
            $objectKey,
            true,
            $category,
            FilterImportGate::EXPORT_DISK,
        );

        $this->info('Import job dispatched.');

        return self::SUCCESS;
    }
}
