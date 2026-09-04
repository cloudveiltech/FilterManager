<?php

use App\Jobs\ProcessTextFilterArchiveUpload;
use App\Models\FilterRulesManager;
use App\Services\FilterImportGate;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

afterEach(function (): void {
    \Mockery::close();
    Storage::forgetDisk(FilterImportGate::EXPORT_DISK);
});

test('discovery dry-run reports gate outcomes without dispatching jobs', function (): void {
    $directory = sys_get_temp_dir().'/filter-import-discovery-'.bin2hex(random_bytes(8));
    mkdir($directory, 0755, true);

    $rulesManager = new class($directory) extends FilterRulesManager
    {
        public function __construct(private readonly string $directory) {}

        public function getFilename($listNamespace, $listCategory, $filename, $separatorChar = '.')
        {
            return $separatorChar.$listNamespace.$separatorChar.$listCategory.$separatorChar.$filename;
        }

        public function getRulesetPath($namespace, $category, $type)
        {
            return $this->directory.'/'.$type.'.txt';
        }
    };

    try {
        foreach (FilterRulesManager::TYPES as $mappedType) {
            $path = $directory.'/'.$mappedType.'.txt';
            file_put_contents($path, "rule\n");
            touch($path, 2000);
        }

        Storage::fake(FilterImportGate::EXPORT_DISK);
        $disk = Storage::disk(FilterImportGate::EXPORT_DISK);
        $metadataDisk = \Mockery::mock(Filesystem::class);
        $metadataDisk->shouldNotReceive('exists');
        $metadataDisk->shouldNotReceive('lastModified');

        foreach ([
            'export.zip',
            'export_Uncategorized.zip',
            'export_movies.zip',
            'export_current.zip',
            'export_unknown.zip',
        ] as $objectKey) {
            $disk->put($objectKey, 'fixture');
        }

        touch($disk->path('export_current.zip'), 1000);

        app()->instance(FilterImportGate::class, new FilterImportGate(
            exportDisk: $metadataDisk,
            rulesManager: $rulesManager,
            clockToleranceSeconds: 0,
            categoryImportState: static function (string $namespace, string $category): ?bool {
                if (strcasecmp($category, 'Uncategorized') === 0) {
                    return false;
                }

                return in_array($category, ['movies', 'current'], true) ? true : null;
            },
        ));

        Bus::fake();

        $this->artisan('filter:discover', ['--dry-run' => true])
            ->expectsOutputToContain('[denied] category=uncategorized object=export_Uncategorized.zip')
            ->expectsOutputToContain('[imported] category=movies object=export_movies.zip')
            ->expectsOutputToContain('[skipped-already-current] category=current object=export_current.zip')
            ->expectsOutputToContain('[not-in-allowlist] category=unknown object=export_unknown.zip')
            ->expectsOutputToContain('imported: 1')
            ->expectsOutputToContain('skipped-already-current: 1')
            ->expectsOutputToContain('not-in-allowlist: 1')
            ->expectsOutputToContain('denied: 1')
            ->expectsOutputToContain('jobs-dispatched: 0')
            ->doesntExpectOutputToContain('category=(unknown) object=export.zip')
            ->assertExitCode(0);

        Bus::assertNothingDispatched();
    } finally {
        foreach (glob($directory.'/*') ?: [] as $path) {
            unlink($path);
        }

        rmdir($directory);
    }
});

test('discovery dispatches an allowed changed category with the export disk', function (): void {
    $directory = sys_get_temp_dir().'/filter-import-discovery-'.bin2hex(random_bytes(8));
    mkdir($directory, 0755, true);

    $rulesManager = new class($directory) extends FilterRulesManager
    {
        public function __construct(private readonly string $directory) {}

        public function getFilename($listNamespace, $listCategory, $filename, $separatorChar = '.')
        {
            return $separatorChar.$listNamespace.$separatorChar.$listCategory.$separatorChar.$filename;
        }

        public function getRulesetPath($namespace, $category, $type)
        {
            return $this->directory.'/'.$type.'.txt';
        }
    };

    try {
        Storage::fake(FilterImportGate::EXPORT_DISK);
        $disk = Storage::disk(FilterImportGate::EXPORT_DISK);
        $metadataDisk = \Mockery::mock(Filesystem::class);
        $metadataDisk->shouldNotReceive('exists');
        $metadataDisk->shouldNotReceive('lastModified');
        $disk->put('export_movies.zip', 'fixture');

        app()->instance(FilterImportGate::class, new FilterImportGate(
            exportDisk: $metadataDisk,
            rulesManager: $rulesManager,
            categoryImportState: static fn (string $namespace, string $category): ?bool => $category === 'movies' ? true : null,
        ));

        Bus::fake();

        $this->artisan('filter:discover')
            ->expectsOutputToContain('[imported] category=movies object=export_movies.zip')
            ->expectsOutputToContain('jobs-dispatched: 1')
            ->assertExitCode(0);

        Bus::assertDispatched(ProcessTextFilterArchiveUpload::class, static function (ProcessTextFilterArchiveUpload $job): bool {
            return $job->listNamespace === 'default'
                && $job->file === 'export_movies.zip'
                && $job->shouldOverwrite === true
                && $job->category === 'movies'
                && $job->disk === 'export';
        });
    } finally {
        foreach (glob($directory.'/*') ?: [] as $path) {
            unlink($path);
        }

        rmdir($directory);
    }
});
