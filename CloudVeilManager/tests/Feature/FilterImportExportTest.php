<?php

use App\Jobs\ProcessTextFilterArchiveUpload;
use App\Models\FilterRulesManager;
use App\Services\FilterImportGate;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Bus;

afterEach(function (): void {
    \Mockery::close();
});

function filterImportExportTestGate(
    string $objectKey,
    int|false|\Throwable $objectLastModified = 2000,
    bool $hasFilterLists = true,
    array $deniedCategories = [],
    bool $writeCurrentRules = false,
): array {
    $directory = sys_get_temp_dir().'/filter-import-export-'.bin2hex(random_bytes(8));
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

    if ($writeCurrentRules) {
        foreach (['rules', 'triggers'] as $type) {
            $path = $directory.'/'.$type.'.txt';
            file_put_contents($path, "rule\n");
            touch($path, 2000);
        }
    }

    $exportDisk = \Mockery::mock(Filesystem::class);
    if ($objectLastModified instanceof \Throwable) {
        $exportDisk->expects('exists')->with($objectKey)->andThrow($objectLastModified);
    } else {
        $exportDisk->expects('exists')->with($objectKey)->andReturn($objectLastModified !== false);

        if ($objectLastModified !== false) {
            $exportDisk->expects('lastModified')->with($objectKey)->andReturn($objectLastModified);
        }
    }

    return [
        new FilterImportGate(
            exportDisk: $exportDisk,
            rulesManager: $rulesManager,
            deniedCategories: $deniedCategories,
            clockToleranceSeconds: 5,
            hasFilterLists: static fn (string $namespace, string $category): bool => $hasFilterLists,
        ),
        $directory,
    ];
}

function removeFilterImportExportTestFixture(string $directory): void
{
    foreach (glob($directory.'/*') ?: [] as $path) {
        unlink($path);
    }

    rmdir($directory);
}

test('dispatches an allowlisted category with the export job arguments', function (): void {
    Bus::fake();
    [$gate, $directory] = filterImportExportTestGate('export_movies.zip');
    $this->app->bind(FilterImportGate::class, static fn (): FilterImportGate => $gate);

    try {
        $this->artisan('filter:import-export', ['category' => 'movies'])
            ->expectsOutput('Outcome: imported')
            ->expectsOutput('Import job dispatched.')
            ->assertExitCode(0);

        Bus::assertDispatched(ProcessTextFilterArchiveUpload::class, function (ProcessTextFilterArchiveUpload $job): bool {
            return $job->listNamespace === 'default'
                && $job->file === 'export_movies.zip'
                && $job->shouldOverwrite === true
                && $job->category === 'movies'
                && $job->disk === 'export';
        });
    } finally {
        removeFilterImportExportTestFixture($directory);
    }
});

test('--force bypasses only the current-file decision', function (): void {
    Bus::fake();
    [$gate, $directory] = filterImportExportTestGate(
        'export_movies.zip',
        writeCurrentRules: true,
    );
    $this->app->bind(FilterImportGate::class, static fn (): FilterImportGate => $gate);

    try {
        $this->artisan('filter:import-export', [
            'category' => 'movies',
            '--force' => true,
        ])
            ->expectsOutput('Outcome: skipped-already-current')
            ->expectsOutput('Force enabled: bypassing the modified-time check only.')
            ->expectsOutput('Import job dispatched.')
            ->assertExitCode(0);

        Bus::assertDispatched(ProcessTextFilterArchiveUpload::class);
    } finally {
        removeFilterImportExportTestFixture($directory);
    }
});

test('--force cannot dispatch a denied category', function (): void {
    Bus::fake();
    [$gate, $directory] = filterImportExportTestGate(
        'export_Uncategorized.zip',
        deniedCategories: ['Uncategorized'],
    );
    $this->app->bind(FilterImportGate::class, static fn (): FilterImportGate => $gate);

    try {
        $this->artisan('filter:import-export', [
            'category' => 'Uncategorized',
            '--force' => true,
        ])
            ->expectsOutput('Outcome: denied')
            ->expectsOutput('Reason: The category is present in filter_imports.deny.')
            ->assertExitCode(0);

        Bus::assertNothingDispatched();
    } finally {
        removeFilterImportExportTestFixture($directory);
    }
});

test('--force cannot dispatch a category outside the allowlist', function (): void {
    Bus::fake();
    [$gate, $directory] = filterImportExportTestGate(
        'export_new-category.zip',
        hasFilterLists: false,
    );
    $this->app->bind(FilterImportGate::class, static fn (): FilterImportGate => $gate);

    try {
        $this->artisan('filter:import-export', [
            'category' => 'new-category',
            '--force' => true,
        ])
            ->expectsOutput('Outcome: not-in-allowlist')
            ->expectsOutput('Reason: No default filter_lists row exists for the category.')
            ->assertExitCode(0);

        Bus::assertNothingDispatched();
    } finally {
        removeFilterImportExportTestFixture($directory);
    }
});

test('returns failure when the export object is missing', function (): void {
    Bus::fake();
    [$gate, $directory] = filterImportExportTestGate(
        'export_movies.zip',
        objectLastModified: false,
    );
    $this->app->bind(FilterImportGate::class, static fn (): FilterImportGate => $gate);

    try {
        $this->artisan('filter:import-export', ['category' => 'movies'])
            ->expectsOutput('Outcome: object-missing')
            ->expectsOutput('Reason: The bucket object does not exist on the export disk.')
            ->assertExitCode(1);

        Bus::assertNothingDispatched();
    } finally {
        removeFilterImportExportTestFixture($directory);
    }
});

test('returns failure when export metadata cannot be read', function (): void {
    Bus::fake();
    [$gate, $directory] = filterImportExportTestGate(
        'export_movies.zip',
        objectLastModified: new RuntimeException('credentials failed'),
    );
    $this->app->bind(FilterImportGate::class, static fn (): FilterImportGate => $gate);

    try {
        $this->artisan('filter:import-export', ['category' => 'movies'])
            ->expectsOutput('Outcome: disk-error')
            ->expectsOutputToContain('Reason: '.FilterImportGate::DISK_ERROR_REASON)
            ->assertExitCode(1);

        Bus::assertNothingDispatched();
    } finally {
        removeFilterImportExportTestFixture($directory);
    }
});
