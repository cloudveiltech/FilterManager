<?php

namespace Tests\Feature;

use App\FilterList;
use App\FilterRulesManager;
use App\Http\Controllers\FilterListController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FilterListControllerArchiveTest extends TestCase
{
    private $originalDefaultConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalDefaultConnection = config('database.default');
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        $schema = Schema::connection('sqlite');
        $schema->create('filter_lists', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('namespace', 64);
            $table->string('category', 64);
            $table->string('type', 32);
            $table->string('file_sha1', 40)->nullable();
            $table->unsignedInteger('entries_count')->default(0);
            $table->timestamps();
            $table->unique(['namespace', 'category', 'type']);
        });
        $schema->create('group_filter_assignments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('filter_list_id');
        });
    }

    protected function tearDown(): void
    {
        FilterList::flushEventListeners();

        $schema = Schema::connection('sqlite');
        $schema->dropIfExists('group_filter_assignments');
        $schema->dropIfExists('filter_lists');
        DB::purge('sqlite');
        config(['database.default' => $this->originalDefaultConnection]);
        DB::setDefaultConnection($this->originalDefaultConnection);

        parent::tearDown();
    }

    public function testAFilterArchiveFailureRemovesEveryRuleFileTouchedByItsCategory()
    {
        $namespace = 'cleanup_test';
        $category = 'q' . bin2hex(random_bytes(6));
        $rulesManager = new FilterRulesManager();
        $filtersPath = $rulesManager->getRulesetPath($namespace, $category, 'rules');
        $triggersPath = $rulesManager->getRulesetPath($namespace, $category, 'triggers');

        $archivePath = tempnam(sys_get_temp_dir(), 'filter-import-');
        unlink($archivePath);
        $archivePath .= '.zip';

        $zip = new \ZipArchive();
        $this->assertSame(true, $zip->open($archivePath, \ZipArchive::CREATE));
        $this->assertSame(true, $zip->addFromString($category . '/domains.txt', "example.com\n"));
        $this->assertSame(true, $zip->addFromString($category . '/urls.txt', "example.org\n"));
        $this->assertSame(true, $zip->addFromString($category . '/triggers.txt', "example.net\n"));
        $this->assertSame(true, $zip->close());

        $failureObservedAfterWrites = false;
        FilterList::updating(function ($list) use (&$failureObservedAfterWrites, $filtersPath, $triggersPath) {
            if (is_file($filtersPath) && is_file($triggersPath)) {
                $failureObservedAfterWrites = true;
                throw new \RuntimeException('forced failure while importing category');
            }
        });

        try {
            $exception = null;

            try {
                (new FilterListController())->processTextFilterArchive($namespace, $archivePath, false);
            } catch (\Throwable $e) {
                $exception = $e;
            }

            $this->assertInstanceOf(\RuntimeException::class, $exception);
            $this->assertSame('forced failure while importing category', $exception->getMessage());
            $this->assertTrue($failureObservedAfterWrites);
            $this->assertFileNotExists($filtersPath);
            $this->assertFileNotExists($triggersPath);
        } finally {
            @unlink($archivePath);
            @unlink($filtersPath);
            @unlink($triggersPath);
            @rmdir(dirname($filtersPath));
            @rmdir(dirname(dirname($filtersPath)));
        }
    }
}
