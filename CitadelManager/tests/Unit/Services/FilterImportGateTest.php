<?php

namespace Tests\Unit\Services;

use App\FilterRulesManager;
use App\Services\FilterImportGate;
use App\Services\FilterImportOutcome;
use PHPUnit\Framework\TestCase;

class FilterImportGateTest extends TestCase
{
    public function testExportZipIsStructurallyExcludedAndProducesNoCategoryCandidate()
    {
        list($directory, $rulesManager) = $this->filterImportGateFixture();

        try {
            $disk = $this->makeExportDisk();

            $decision = $this->makeFilterImportGate($rulesManager, true, [], 0, $disk)
                ->decide('export.zip', 1000);

            $this->assertNull($decision);
            $this->assertSame(0, $disk->existsCalls);
            $this->assertSame(0, $disk->lastModifiedCalls);
        } finally {
            $this->removeFilterImportFixture($directory);
        }
    }

    public function testACategoryWithNoLocalRuleFilesMustBeImported()
    {
        list($directory, $rulesManager) = $this->filterImportGateFixture();

        try {
            $decision = $this->makeFilterImportGate($rulesManager)
                ->decide('export_movies.zip', 2000);

            $this->assertSame(FilterImportOutcome::IMPORTED, $decision->outcome);
            $this->assertTrue($decision->shouldImport());
            $this->assertContains('missing', $decision->reason);
        } finally {
            $this->removeFilterImportFixture($directory);
        }
    }

    public function testTheNewestLocalRuleFileControlsChangeDetection()
    {
        list($directory, $rulesManager) = $this->filterImportGateFixture();

        try {
            $this->writeFilterImportRuleFile($directory, 'rules', 3000);
            $this->writeFilterImportRuleFile($directory, 'triggers', 1000);

            $disk = $this->makeExportDisk();

            $decision = $this->makeFilterImportGate($rulesManager, true, [], 0, $disk)
                ->decide('export_movies.zip', 2500);

            $this->assertSame(FilterImportOutcome::SKIPPED_ALREADY_CURRENT, $decision->outcome);
            $this->assertSame(3000, $decision->newestLocalMtime);
            $this->assertSame(0, $disk->existsCalls);
            $this->assertSame(0, $disk->lastModifiedCalls);
        } finally {
            $this->removeFilterImportFixture($directory);
        }
    }

    public function testAMissingFiltersFileWithAnAncientTriggersFileCausesAnImport()
    {
        list($directory, $rulesManager) = $this->filterImportGateFixture();

        try {
            $this->writeFilterImportRuleFile($directory, 'triggers', 1000);

            $decision = $this->makeFilterImportGate($rulesManager)
                ->decide('export_movies.zip', 2500);

            $this->assertSame(FilterImportOutcome::IMPORTED, $decision->outcome);
            $this->assertSame(1000, $decision->newestLocalMtime);
            $this->assertContains('newest local rule file', $decision->reason);
        } finally {
            $this->removeFilterImportFixture($directory);
        }
    }

    public function testAFreshFiltersFileKeepsACategoryCurrentWhenTriggersIsMissing()
    {
        list($directory, $rulesManager) = $this->filterImportGateFixture();

        try {
            $this->writeFilterImportRuleFile($directory, 'rules', 3000);

            $decision = $this->makeFilterImportGate($rulesManager)
                ->decide('export_movies.zip', 2500);

            $this->assertSame(FilterImportOutcome::SKIPPED_ALREADY_CURRENT, $decision->outcome);
            $this->assertSame(3000, $decision->newestLocalMtime);
        } finally {
            $this->removeFilterImportFixture($directory);
        }
    }

    public function testACategoryWhoseLocalFilesAreCurrentIsSkipped()
    {
        list($directory, $rulesManager) = $this->filterImportGateFixture();

        try {
            $this->writeFilterImportRuleFile($directory, 'rules', 2000);
            $this->writeFilterImportRuleFile($directory, 'triggers', 2000);

            $decision = $this->makeFilterImportGate($rulesManager)
                ->decide('export_movies.zip', 1999);

            $this->assertSame(FilterImportOutcome::SKIPPED_ALREADY_CURRENT, $decision->outcome);
            $this->assertFalse($decision->shouldImport());
            $this->assertSame(2000, $decision->newestLocalMtime);
        } finally {
            $this->removeFilterImportFixture($directory);
        }
    }

    public function testTheClockTolerancePreventsANearEqualMtimeFromReimporting()
    {
        list($directory, $rulesManager) = $this->filterImportGateFixture();

        try {
            $this->writeFilterImportRuleFile($directory, 'rules', 1000);
            $this->writeFilterImportRuleFile($directory, 'triggers', 1000);

            $decision = $this->makeFilterImportGate($rulesManager, true, [], 5)
                ->decide('export_movies.zip', 1004);

            $this->assertSame(FilterImportOutcome::SKIPPED_ALREADY_CURRENT, $decision->outcome);
        } finally {
            $this->removeFilterImportFixture($directory);
        }
    }

    public function testADeniedCategoryIsNeverImportableEvenWhenFilterListRowsExist()
    {
        list($directory, $rulesManager) = $this->filterImportGateFixture();

        try {
            $decision = $this->makeFilterImportGate($rulesManager, true, ['movies'])
                ->decide('export_movies.zip', 2000);

            $this->assertSame(FilterImportOutcome::DENIED, $decision->outcome);
            $this->assertFalse($decision->shouldImport());
        } finally {
            $this->removeFilterImportFixture($directory);
        }
    }

    public function testADifferentlyCasedDeniedCategoryIsStillRefused()
    {
        list($directory, $rulesManager) = $this->filterImportGateFixture();

        try {
            $decision = $this->makeFilterImportGate($rulesManager, true, ['Uncategorized'])
                ->decide('export_uncategorized.zip', 2000);

            $this->assertSame(FilterImportOutcome::DENIED, $decision->outcome);
            $this->assertFalse($decision->shouldImport());
        } finally {
            $this->removeFilterImportFixture($directory);
        }
    }

    public function testACategoryWithoutADefaultFilterListRowIsNotInTheAllowlist()
    {
        list($directory, $rulesManager) = $this->filterImportGateFixture();

        try {
            $decision = $this->makeFilterImportGate($rulesManager, false)
                ->decide('export_movies.zip', 2000);

            $this->assertSame(FilterImportOutcome::NOT_IN_ALLOWLIST, $decision->outcome);
            $this->assertFalse($decision->shouldImport());
        } finally {
            $this->removeFilterImportFixture($directory);
        }
    }

    public function testAMissingBucketObjectIsDistinguishableFromAnEmptyImportRun()
    {
        list($directory, $rulesManager) = $this->filterImportGateFixture();

        try {
            $disk = $this->makeExportDisk(false);

            $decision = $this->makeFilterImportGate($rulesManager, true, [], 0, $disk)
                ->decide('export_movies.zip');

            $this->assertSame(FilterImportOutcome::OBJECT_MISSING, $decision->outcome);
            $this->assertFalse($decision->shouldImport());
            $this->assertSame(1, $disk->existsCalls);
            $this->assertSame(0, $disk->lastModifiedCalls);
        } finally {
            $this->removeFilterImportFixture($directory);
        }
    }

    public function testABucketMetadataFailureIsReportedAsADiskError()
    {
        list($directory, $rulesManager) = $this->filterImportGateFixture();

        try {
            $disk = $this->makeExportDisk(true, 0, new \RuntimeException('credentials failed'));

            $decision = $this->makeFilterImportGate($rulesManager, true, [], 0, $disk)
                ->decide('export_movies.zip');

            $this->assertSame(FilterImportOutcome::DISK_ERROR, $decision->outcome);
            $this->assertContains('credentials failed', $decision->reason);
            $this->assertSame(1, $disk->existsCalls);
            $this->assertSame(0, $disk->lastModifiedCalls);
        } finally {
            $this->removeFilterImportFixture($directory);
        }
    }

    /**
     * @return array
     */
    private function filterImportGateFixture()
    {
        $directory = sys_get_temp_dir().'/filter-import-gate-'.bin2hex(random_bytes(8));
        mkdir($directory, 0755, true);

        $rulesManager = new class($directory) extends FilterRulesManager
        {
            private $directory;

            public function __construct($directory)
            {
                $this->directory = $directory;
            }

            public function getFilename($listNamespace, $listCategory, $filename, $separatorChar = '.')
            {
                return $separatorChar.$listNamespace.
                    $separatorChar.$listCategory.$separatorChar.$filename;
            }

            public function getRulesetPath($namespace, $category, $type)
            {
                return $this->directory.'/'.$type.'.txt';
            }
        };

        return [$directory, $rulesManager];
    }

    private function writeFilterImportRuleFile($directory, $type, $mtime)
    {
        $path = $directory.'/'.$type.'.txt';
        file_put_contents($path, "rule\n");
        touch($path, $mtime);
    }

    private function removeFilterImportFixture($directory)
    {
        foreach (glob($directory.'/*') ?: [] as $path) {
            unlink($path);
        }

        rmdir($directory);
    }

    private function makeExportDisk($exists = true, $lastModified = 0, $existsException = null)
    {
        return new class($exists, $lastModified, $existsException)
        {
            public $existsCalls = 0;
            public $lastModifiedCalls = 0;
            private $existsResult;
            private $lastModifiedResult;
            private $existsException;

            public function __construct($existsResult, $lastModifiedResult, $existsException)
            {
                $this->existsResult = $existsResult;
                $this->lastModifiedResult = $lastModifiedResult;
                $this->existsException = $existsException;
            }

            public function exists($path)
            {
                $this->existsCalls++;

                if ($this->existsException !== null) {
                    throw $this->existsException;
                }

                return $this->existsResult;
            }

            public function lastModified($path)
            {
                $this->lastModifiedCalls++;

                return $this->lastModifiedResult;
            }
        };
    }

    private function makeFilterImportGate(
        $rulesManager,
        $hasFilterLists = true,
        $deniedCategories = [],
        $clockToleranceSeconds = 0,
        $exportDisk = null
    ) {
        return new FilterImportGate(
            $exportDisk ?: $this->makeExportDisk(),
            $rulesManager,
            $deniedCategories,
            $clockToleranceSeconds,
            function ($namespace, $category) use ($hasFilterLists) {
                return $hasFilterLists;
            }
        );
    }
}
