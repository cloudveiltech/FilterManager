<?php

use App\Models\FilterRulesManager;
use App\Services\FilterImportGate;
use App\Services\FilterImportOutcome;
use Illuminate\Contracts\Filesystem\Filesystem;

uses(Tests\TestCase::class);

afterEach(function (): void {
    \Mockery::close();
});

function filterImportGateFixture(): array
{
    $directory = sys_get_temp_dir().'/filter-import-gate-'.bin2hex(random_bytes(8));
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

    return [$directory, $rulesManager];
}

function writeFilterImportRuleFile(string $directory, string $type, int $mtime): void
{
    $path = $directory.'/'.$type.'.txt';
    file_put_contents($path, "rule\n");
    touch($path, $mtime);
}

function removeFilterImportFixture(string $directory): void
{
    foreach (glob($directory.'/*') ?: [] as $path) {
        unlink($path);
    }

    rmdir($directory);
}

// $importState mirrors the gate's contract: null means the category has no
// filter_lists rows, false means importing is turned off on one of them.
function makeFilterImportGate(
    FilterRulesManager $rulesManager,
    ?bool $importState = true,
    int $clockToleranceSeconds = 0,
    ?Filesystem $exportDisk = null,
): FilterImportGate {
    return new FilterImportGate(
        exportDisk: $exportDisk ?? \Mockery::mock(Filesystem::class),
        rulesManager: $rulesManager,
        clockToleranceSeconds: $clockToleranceSeconds,
        categoryImportState: static fn (string $namespace, string $category): ?bool => $importState,
    );
}

test('export.zip is structurally excluded and produces no category candidate', function (): void {
    [$directory, $rulesManager] = filterImportGateFixture();

    try {
        $disk = \Mockery::mock(Filesystem::class);
        $disk->shouldNotReceive('exists');
        $disk->shouldNotReceive('lastModified');

        $decision = makeFilterImportGate($rulesManager, exportDisk: $disk)->decide('export.zip', 1000);

        expect($decision)->toBeNull();
    } finally {
        removeFilterImportFixture($directory);
    }
});

test('object key categories are normalized before policy and local file checks', function (): void {
    [$directory, $rulesManager] = filterImportGateFixture();
    $checkedCategory = null;

    try {
        $gate = new FilterImportGate(
            exportDisk: \Mockery::mock(Filesystem::class),
            rulesManager: $rulesManager,
            clockToleranceSeconds: 0,
            categoryImportState: static function (string $namespace, string $category) use (&$checkedCategory): bool {
                $checkedCategory = $category;

                return true;
            },
        );

        $decision = $gate->decide('export_Uncategorized.zip', 2000);

        expect($checkedCategory)->toBe('uncategorized')
            ->and($decision->category)->toBe('uncategorized')
            ->and($decision->outcome)->toBe(FilterImportOutcome::IMPORTED);
    } finally {
        removeFilterImportFixture($directory);
    }
});

test('a category with no local rule files must be imported', function (): void {
    [$directory, $rulesManager] = filterImportGateFixture();

    try {
        $decision = makeFilterImportGate($rulesManager)->decide('export_movies.zip', 2000);

        expect($decision->outcome)->toBe(FilterImportOutcome::IMPORTED)
            ->and($decision->shouldImport())->toBeTrue()
            ->and($decision->reason)->toContain('missing');
    } finally {
        removeFilterImportFixture($directory);
    }
});

test('the newest local rule file controls change detection', function (): void {
    [$directory, $rulesManager] = filterImportGateFixture();

    try {
        writeFilterImportRuleFile($directory, 'rules', 3000);
        writeFilterImportRuleFile($directory, 'triggers', 1000);

        $decision = makeFilterImportGate($rulesManager)->decide('export_movies.zip', 2500);

        expect($decision->outcome)->toBe(FilterImportOutcome::SKIPPED_ALREADY_CURRENT);
    } finally {
        removeFilterImportFixture($directory);
    }
});

test('a missing Filters file with an ancient Triggers file causes an import', function (): void {
    [$directory, $rulesManager] = filterImportGateFixture();

    try {
        writeFilterImportRuleFile($directory, 'triggers', 1000);

        $decision = makeFilterImportGate($rulesManager)->decide('export_movies.zip', 2500);

        expect($decision->outcome)->toBe(FilterImportOutcome::IMPORTED)
            ->and($decision->reason)->toContain('newest local rule file');
    } finally {
        removeFilterImportFixture($directory);
    }
});

test('a fresh Filters file keeps a category current when Triggers is missing', function (): void {
    [$directory, $rulesManager] = filterImportGateFixture();

    try {
        writeFilterImportRuleFile($directory, 'rules', 3000);

        $decision = makeFilterImportGate($rulesManager)->decide('export_movies.zip', 2500);

        expect($decision->outcome)->toBe(FilterImportOutcome::SKIPPED_ALREADY_CURRENT);
    } finally {
        removeFilterImportFixture($directory);
    }
});

test('a category whose local files are current is skipped', function (): void {
    [$directory, $rulesManager] = filterImportGateFixture();

    try {
        writeFilterImportRuleFile($directory, 'rules', 2000);
        writeFilterImportRuleFile($directory, 'triggers', 2000);

        $decision = makeFilterImportGate($rulesManager)->decide('export_movies.zip', 1999);

        expect($decision->outcome)->toBe(FilterImportOutcome::SKIPPED_ALREADY_CURRENT)
            ->and($decision->shouldImport())->toBeFalse();
    } finally {
        removeFilterImportFixture($directory);
    }
});

test('the clock tolerance prevents a near-equal mtime from reimporting', function (): void {
    [$directory, $rulesManager] = filterImportGateFixture();

    try {
        writeFilterImportRuleFile($directory, 'rules', 1000);
        writeFilterImportRuleFile($directory, 'triggers', 1000);

        $decision = makeFilterImportGate(
            $rulesManager,
            clockToleranceSeconds: 5,
        )->decide('export_movies.zip', 1004);

        expect($decision->outcome)->toBe(FilterImportOutcome::SKIPPED_ALREADY_CURRENT);
    } finally {
        removeFilterImportFixture($directory);
    }
});

test('a category with importing turned off is never importable', function (): void {
    [$directory, $rulesManager] = filterImportGateFixture();

    try {
        $decision = makeFilterImportGate(
            $rulesManager,
            importState: false,
        )->decide('export_movies.zip', 2000);

        expect($decision->outcome)->toBe(FilterImportOutcome::DENIED)
            ->and($decision->shouldImport())->toBeFalse();
    } finally {
        removeFilterImportFixture($directory);
    }
});

test('a category with importing turned off reports why', function (): void {
    [$directory, $rulesManager] = filterImportGateFixture();

    try {
        $decision = makeFilterImportGate(
            $rulesManager,
            importState: false,
        )->decide('export_uncategorized.zip', 2000);

        expect($decision->outcome)->toBe(FilterImportOutcome::DENIED)
            ->and($decision->reason)->toContain('turned off');
    } finally {
        removeFilterImportFixture($directory);
    }
});

test('a category without a default filter list row is not in the allowlist', function (): void {
    [$directory, $rulesManager] = filterImportGateFixture();

    try {
        $decision = makeFilterImportGate($rulesManager, importState: null)
            ->decide('export_movies.zip', 2000);

        expect($decision->outcome)->toBe(FilterImportOutcome::NOT_IN_ALLOWLIST)
            ->and($decision->shouldImport())->toBeFalse();
    } finally {
        removeFilterImportFixture($directory);
    }
});

test('a missing bucket object is distinguishable from an empty import run', function (): void {
    [$directory, $rulesManager] = filterImportGateFixture();

    try {
        $disk = \Mockery::mock(Filesystem::class);
        $disk->expects('exists')->with('export_movies.zip')->andReturnFalse();

        $decision = makeFilterImportGate($rulesManager, exportDisk: $disk)
            ->decide('export_movies.zip');

        expect($decision->outcome)->toBe(FilterImportOutcome::OBJECT_MISSING)
            ->and($decision->shouldImport())->toBeFalse();
    } finally {
        removeFilterImportFixture($directory);
    }
});

test('a bucket metadata failure is reported as a disk error', function (): void {
    [$directory, $rulesManager] = filterImportGateFixture();

    try {
        $disk = \Mockery::mock(Filesystem::class);
        $disk->expects('exists')->with('export_movies.zip')->andThrow(new RuntimeException('credentials failed'));

        $decision = makeFilterImportGate($rulesManager, exportDisk: $disk)
            ->decide('export_movies.zip');

        expect($decision->outcome)->toBe(FilterImportOutcome::DISK_ERROR)
            ->and($decision->reason)->toBe(FilterImportGate::DISK_ERROR_REASON);
    } finally {
        removeFilterImportFixture($directory);
    }
});
