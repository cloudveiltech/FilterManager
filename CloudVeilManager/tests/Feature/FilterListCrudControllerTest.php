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

function filterListCrudControllerGateFixture(
    string $objectKey,
    int|false|Throwable $objectLastModified = 2000,
    ?bool $importState = true,
): array {
    $directory = sys_get_temp_dir().'/filter-list-crud-'.bin2hex(random_bytes(8));
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

    $exportDisk = \Mockery::mock(Filesystem::class);
    if ($objectLastModified instanceof Throwable) {
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
            clockToleranceSeconds: 5,
            categoryImportState: static fn (string $namespace, string $category): ?bool => $importState,
        ),
        $directory,
    ];
}

function removeFilterListCrudControllerFixture(string $directory): void
{
    foreach (glob($directory.'/*') ?: [] as $path) {
        unlink($path);
    }

    rmdir($directory);
}

test('adoption refuses the all-category export', function (): void {
    Bus::fake();

    foreach (['/admin/update', '/admin/update?file=export.zip'] as $url) {
        $this->withoutMiddleware()
            ->get($url)
            ->assertBadRequest()
            ->assertSee('The all-category export cannot be imported');
    }

    Bus::assertNothingDispatched();
});

test('adoption dispatches an unknown category from the export disk', function (): void {
    Bus::fake();
    [$gate, $directory] = filterListCrudControllerGateFixture(
        'export_new-category.zip',
        importState: null,
    );
    $this->app->bind(FilterImportGate::class, static fn (): FilterImportGate => $gate);

    try {
        $response = $this->withoutMiddleware()->get('/admin/update?file=export_new-category.zip');

        $response->assertOk()
            ->assertSee('Import has been triggered for category [new-category]')
            ->assertSee('export disk object [export_new-category.zip]');

        Bus::assertDispatched(ProcessTextFilterArchiveUpload::class, static function (ProcessTextFilterArchiveUpload $job): bool {
            return $job->listNamespace === 'default'
                && $job->file === 'export_new-category.zip'
                && $job->shouldOverwrite === true
                && $job->category === 'new-category'
                && $job->disk === 'export';
        });
    } finally {
        removeFilterListCrudControllerFixture($directory);
    }
});

test('adoption refuses a denied category by the gate policy', function (): void {
    Bus::fake();
    [$gate, $directory] = filterListCrudControllerGateFixture(
        'export_Uncategorized.zip',
        importState: false,
    );
    $this->app->bind(FilterImportGate::class, static fn (): FilterImportGate => $gate);

    try {
        $response = $this->withoutMiddleware()->get('/admin/update?file=export_Uncategorized.zip');

        $response->assertForbidden()
            ->assertSee('Import refused for category [Uncategorized]')
            ->assertSee('Importing is turned off');

        Bus::assertNothingDispatched();
    } finally {
        removeFilterListCrudControllerFixture($directory);
    }
});

test('adoption reports a missing export object', function (): void {
    Bus::fake();
    [$gate, $directory] = filterListCrudControllerGateFixture(
        'export_movies.zip',
        objectLastModified: false,
    );
    $this->app->bind(FilterImportGate::class, static fn (): FilterImportGate => $gate);

    try {
        $response = $this->withoutMiddleware()->get('/admin/update?file=export_movies.zip');

        $response->assertNotFound()
            ->assertSee('Unable to import category [movies]')
            ->assertSee('The bucket object does not exist on the export disk.');

        Bus::assertNothingDispatched();
    } finally {
        removeFilterListCrudControllerFixture($directory);
    }
});

test('adoption reports an export disk error', function (): void {
    Bus::fake();
    [$gate, $directory] = filterListCrudControllerGateFixture(
        'export_movies.zip',
        objectLastModified: new RuntimeException('credentials failed'),
    );
    $this->app->bind(FilterImportGate::class, static fn (): FilterImportGate => $gate);

    try {
        $response = $this->withoutMiddleware()->get('/admin/update?file=export_movies.zip');

        $response->assertServerError()
            ->assertSee('Unable to import category [movies]: export disk error')
            ->assertSee(FilterImportGate::DISK_ERROR_REASON);

        Bus::assertNothingDispatched();
    } finally {
        removeFilterListCrudControllerFixture($directory);
    }
});
