<?php

namespace Tests\Feature;

use App\Console\Commands\DiscoverFilterImports;
use App\Console\Commands\FilterImportExport;
use App\Console\Kernel;
use App\FilterRulesManager;
use App\Jobs\ProcessTextFilterArchiveUpload;
use App\Services\FilterImportGate;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

class FilterImportCommandsTest extends TestCase
{
    /**
     * @var array
     */
    private $fixtureDirectories = [];

    protected function tearDown()
    {
        foreach ($this->fixtureDirectories as $directory) {
            foreach (glob($directory.'/*') ?: [] as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }

            if (is_dir($directory)) {
                rmdir($directory);
            }
        }

        Storage::forgetDisk(FilterImportGate::EXPORT_DISK);

        parent::tearDown();
    }

    public function testDiscoveryDryRunReportsEveryGateOutcomeWithoutDispatching()
    {
        $directory = $this->makeFixtureDirectory();
        $rulesManager = $this->makeRulesManager($directory);

        foreach (FilterRulesManager::TYPES as $mappedType) {
            $path = $directory.'/'.$mappedType.'.txt';
            file_put_contents($path, "rule\n");
            touch($path, 2000);
        }

        Storage::fake(FilterImportGate::EXPORT_DISK);
        $disk = Storage::disk(FilterImportGate::EXPORT_DISK);

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

        $gate = new FilterImportGate(
            $this->metadataDiskThatMustNotBeRead(),
            $rulesManager,
            ['Uncategorized'],
            0,
            function ($namespace, $category) {
                return in_array($category, ['movies', 'current'], true);
            }
        );
        $this->bindGate($gate);
        Bus::fake();

        $this->assertCommandOutput(
            'filter:discover',
            ['--dry-run' => true],
            [
                'Filter import discovery started (dry-run; no jobs will be dispatched).',
                '[denied] category=Uncategorized object=export_Uncategorized.zip - The category is present in filter_imports.deny.',
                '[skipped-already-current] category=current object=export_current.zip - The newest local rule file is current within the clock tolerance.',
                '[imported] category=movies object=export_movies.zip - The bucket object is newer than the newest local rule file. - would dispatch (dry-run)',
                '[not-in-allowlist] category=unknown object=export_unknown.zip - No default filter_lists row exists for the category.',
                'imported: 1',
                'skipped-already-current: 1',
                'not-in-allowlist: 1',
                'denied: 1',
                'object-missing: 0',
                'disk-error: 0',
                'objects-seen: 5',
                'jobs-dispatched: 0',
                'dispatch-errors: 0',
                'gate-errors: 0',
                'listing-errors: 0',
            ],
            0
        );

        Bus::assertNotDispatched(ProcessTextFilterArchiveUpload::class);
    }

    public function testDiscoveryUsesFlysystemV1TimestampAndDispatchesChangedCategory()
    {
        $directory = $this->makeFixtureDirectory();
        $rulesManager = $this->makeRulesManager($directory);

        Storage::fake(FilterImportGate::EXPORT_DISK);
        Storage::disk(FilterImportGate::EXPORT_DISK)->put('export_movies.zip', 'fixture');

        $gate = new FilterImportGate(
            $this->metadataDiskThatMustNotBeRead(),
            $rulesManager,
            [],
            0,
            function ($namespace, $category) {
                return $category === 'movies';
            }
        );
        $this->bindGate($gate);
        Bus::fake();

        $this->assertCommandOutput(
            'filter:discover',
            [],
            [
                'Filter import discovery started.',
                '[imported] category=movies object=export_movies.zip - A local rule file is missing: .default.movies.rules.txt. - dispatched',
                'imported: 1',
                'jobs-dispatched: 1',
            ],
            0
        );

        Bus::assertDispatched(ProcessTextFilterArchiveUpload::class, function ($job) {
            return $job->listNamespace === 'default'
                && $job->file === 'export_movies.zip'
                && $job->shouldOverwrite === true
                && $job->category === 'movies'
                && $job->disk === 'export';
        });
    }

    public function testDiscoveryReportsListingFailureAsDiskError()
    {
        Storage::set(FilterImportGate::EXPORT_DISK, new class
        {
            public function listContents($directory, $recursive)
            {
                throw new RuntimeException('credentials failed');
            }
        });

        Bus::fake();

        $this->assertCommandOutput(
            'filter:discover',
            ['--dry-run' => true],
            [
                'Filter import discovery started (dry-run; no jobs will be dispatched).',
                '[disk-error] category=(listing) object=export disk - Unable to list the export disk: credentials failed',
                'imported: 0',
                'skipped-already-current: 0',
                'not-in-allowlist: 0',
                'denied: 0',
                'object-missing: 0',
                'disk-error: 1',
                'objects-seen: 0',
                'jobs-dispatched: 0',
                'listing-errors: 1',
            ],
            1
        );

        Bus::assertNotDispatched(ProcessTextFilterArchiveUpload::class);
    }

    public function testManualImportDispatchesTheExpectedJobArguments()
    {
        $directory = $this->makeFixtureDirectory();
        $gate = $this->makeGate($directory, true, 2000, true);

        Storage::fake(FilterImportGate::EXPORT_DISK);
        $this->bindGate($gate);
        Bus::fake();

        $this->assertCommandOutput(
            'filter:import-export',
            ['category' => 'movies'],
            [
                'Category: movies',
                'Object: export_movies.zip',
                'Outcome: imported',
                'Import job dispatched.',
            ],
            0
        );

        Bus::assertDispatched(ProcessTextFilterArchiveUpload::class, function ($job) {
            return $job->listNamespace === 'default'
                && $job->file === 'export_movies.zip'
                && $job->shouldOverwrite === true
                && $job->category === 'movies'
                && $job->disk === 'export';
        });
    }

    public function testManualForceBypassesOnlyTheCurrentMtimeDecision()
    {
        $directory = $this->makeFixtureDirectory();
        $this->writeCurrentRules($directory, 2000);
        $gate = $this->makeGate($directory, true, 1000, true, [], 0);

        Storage::fake(FilterImportGate::EXPORT_DISK);
        $this->bindGate($gate);
        Bus::fake();

        $this->assertCommandOutput(
            'filter:import-export',
            [
                'category' => 'movies',
                '--force' => true,
            ],
            [
                'Outcome: skipped-already-current',
                'Force enabled: bypassing the modified-time check only.',
                'Import job dispatched.',
            ],
            0
        );

        Bus::assertDispatched(ProcessTextFilterArchiveUpload::class);
    }

    public function testManualCurrentSkipSucceedsWithoutForce()
    {
        $directory = $this->makeFixtureDirectory();
        $this->writeCurrentRules($directory, 2000);
        $gate = $this->makeGate($directory, true, 1000, true, [], 0);

        Storage::fake(FilterImportGate::EXPORT_DISK);
        $this->bindGate($gate);
        Bus::fake();

        $this->assertCommandOutput(
            'filter:import-export',
            ['category' => 'movies'],
            ['Outcome: skipped-already-current'],
            0
        );

        Bus::assertNotDispatched(ProcessTextFilterArchiveUpload::class);
    }

    public function testManualForceCannotBypassDeniedCategory()
    {
        $directory = $this->makeFixtureDirectory();
        $gate = $this->makeGate($directory, true, 2000, true, ['Uncategorized']);

        Storage::fake(FilterImportGate::EXPORT_DISK);
        $this->bindGate($gate);
        Bus::fake();

        $this->assertCommandOutput(
            'filter:import-export',
            [
                'category' => 'Uncategorized',
                '--force' => true,
            ],
            [
                'Outcome: denied',
                'Reason: The category is present in filter_imports.deny.',
            ],
            0
        );

        Bus::assertNotDispatched(ProcessTextFilterArchiveUpload::class);
    }

    public function testManualForceCannotBypassTheAllowlist()
    {
        $directory = $this->makeFixtureDirectory();
        $gate = $this->makeGate($directory, true, 2000, false);

        Storage::fake(FilterImportGate::EXPORT_DISK);
        $this->bindGate($gate);
        Bus::fake();

        $this->assertCommandOutput(
            'filter:import-export',
            [
                'category' => 'new-category',
                '--force' => true,
            ],
            [
                'Outcome: not-in-allowlist',
                'Reason: No default filter_lists row exists for the category.',
            ],
            0
        );

        Bus::assertNotDispatched(ProcessTextFilterArchiveUpload::class);
    }

    public function testManualImportFailsWhenTheObjectIsMissing()
    {
        $directory = $this->makeFixtureDirectory();
        $gate = $this->makeGate($directory, false, 2000, true);

        Storage::fake(FilterImportGate::EXPORT_DISK);
        $this->bindGate($gate);
        Bus::fake();

        $this->assertCommandOutput(
            'filter:import-export',
            ['category' => 'movies'],
            [
                'Outcome: object-missing',
                'Reason: The bucket object does not exist on the export disk.',
            ],
            1
        );

        Bus::assertNotDispatched(ProcessTextFilterArchiveUpload::class);
    }

    public function testManualImportFailsWhenObjectMetadataCannotBeRead()
    {
        $directory = $this->makeFixtureDirectory();
        $gate = $this->makeGate(
            $directory,
            true,
            2000,
            true,
            [],
            0,
            new class
            {
                public function exists($path)
                {
                    throw new RuntimeException('credentials failed');
                }
            }
        );

        Storage::fake(FilterImportGate::EXPORT_DISK);
        $this->bindGate($gate);
        Bus::fake();

        $this->assertCommandOutput(
            'filter:import-export',
            ['category' => 'movies'],
            [
                'Outcome: disk-error',
                'Reason: Unable to read the bucket object metadata: credentials failed',
            ],
            1
        );

        Bus::assertNotDispatched(ProcessTextFilterArchiveUpload::class);
    }

    public function testBothCommandsAreRegisteredAndDiscoveryIsScheduledEveryFifteenMinutesWithoutOverlap()
    {
        $kernel = $this->app->make(Kernel::class);
        $reflection = new \ReflectionClass($kernel);
        $commands = $reflection->getProperty('commands');
        $commands->setAccessible(true);

        $this->assertContains(DiscoverFilterImports::class, $commands->getValue($kernel));
        $this->assertContains(FilterImportExport::class, $commands->getValue($kernel));

        $schedule = new Schedule;
        $scheduleMethod = $reflection->getMethod('schedule');
        $scheduleMethod->setAccessible(true);
        $scheduleMethod->invoke($kernel, $schedule);

        $discoveryEvents = array_values(array_filter($schedule->events(), function ($event) {
            return strpos($event->command, 'filter:discover') !== false;
        }));

        $this->assertCount(1, $discoveryEvents);
        $this->assertSame('*/15 * * * *', $discoveryEvents[0]->expression);
        $this->assertTrue($discoveryEvents[0]->withoutOverlapping);
    }

    private function assertCommandOutput($commandName, array $parameters, array $expectedOutput, $expectedExitCode)
    {
        list($exitCode, $output) = $this->runCommand($commandName, $parameters);

        $this->assertSame($expectedExitCode, $exitCode);

        foreach ($expectedOutput as $expectedLine) {
            $this->assertContains($expectedLine, $output);
        }
    }

    private function bindGate(FilterImportGate $gate)
    {
        $this->app->bind(FilterImportGate::class, function () use ($gate) {
            return $gate;
        });
    }

    private function runCommand($commandName, array $parameters = [])
    {
        $kernel = $this->app->make(Kernel::class);
        $reflection = new \ReflectionClass($kernel);
        $getArtisan = $reflection->getMethod('getArtisan');
        $getArtisan->setAccessible(true);
        $artisan = $getArtisan->invoke($kernel);
        $command = $artisan->find($commandName);
        $tester = new CommandTester($command);
        $input = array_merge(['command' => $commandName], $parameters);
        $exitCode = $tester->execute($input, ['interactive' => false]);

        return [$exitCode, $tester->getDisplay()];
    }

    private function makeFixtureDirectory()
    {
        $directory = sys_get_temp_dir().'/filter-import-command-'.bin2hex(random_bytes(8));
        mkdir($directory, 0755, true);
        $this->fixtureDirectories[] = $directory;

        return $directory;
    }

    private function makeRulesManager($directory)
    {
        return new class($directory) extends FilterRulesManager
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
    }

    private function makeGate(
        $directory,
        $exists,
        $lastModified,
        $hasFilterLists,
        $deniedCategories = [],
        $clockToleranceSeconds = 0,
        $exportDisk = null
    ) {
        return new FilterImportGate(
            $exportDisk ?: $this->makeExportDisk($exists, $lastModified),
            $this->makeRulesManager($directory),
            $deniedCategories,
            $clockToleranceSeconds,
            function ($namespace, $category) use ($hasFilterLists) {
                return $hasFilterLists;
            }
        );
    }

    private function makeExportDisk($exists, $lastModified)
    {
        return new class($exists, $lastModified)
        {
            private $existsResult;
            private $lastModifiedResult;

            public function __construct($existsResult, $lastModifiedResult)
            {
                $this->existsResult = $existsResult;
                $this->lastModifiedResult = $lastModifiedResult;
            }

            public function exists($path)
            {
                return $this->existsResult;
            }

            public function lastModified($path)
            {
                return $this->lastModifiedResult;
            }
        };
    }

    private function metadataDiskThatMustNotBeRead()
    {
        return new class
        {
            public function exists($path)
            {
                throw new RuntimeException('The discovery command made an unnecessary exists request.');
            }

            public function lastModified($path)
            {
                throw new RuntimeException('The discovery command made an unnecessary metadata request.');
            }
        };
    }

    private function writeCurrentRules($directory, $mtime)
    {
        foreach (FilterRulesManager::TYPES as $mappedType) {
            $path = $directory.'/'.$mappedType.'.txt';
            file_put_contents($path, "rule\n");
            touch($path, $mtime);
        }
    }
}
