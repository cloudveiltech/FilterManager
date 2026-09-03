<?php

use App\Jobs\ProcessTextFilterArchiveUpload;
use App\Models\FilterList;
use App\Models\FilterRulesManager;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('group_filter_assignments');
    Schema::dropIfExists('filter_lists');

    Schema::create('filter_lists', function (Blueprint $table) {
        $table->id();
        $table->string('namespace');
        $table->string('category');
        $table->string('type');
        $table->string('file_sha1')->nullable();
        $table->unsignedInteger('entries_count')->default(0);
        $table->boolean('import_enabled')->default(true);
        $table->timestamps();
    });

    Schema::create('group_filter_assignments', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('group_id');
        $table->unsignedBigInteger('filter_list_id');
    });
});

afterEach(function () {
    FilterList::flushEventListeners();
    Schema::dropIfExists('group_filter_assignments');
    Schema::dropIfExists('filter_lists');
});

test('only one pending import is queued for the same category', function () {
    config()->set('cache.default', 'array');
    Queue::fake();

    ProcessTextFilterArchiveUpload::dispatch('default', 'export_movies.zip', true, 'movies', 'export');
    ProcessTextFilterArchiveUpload::dispatch('default', 'export_movies.zip', true, 'movies', 'export');
    ProcessTextFilterArchiveUpload::dispatch('default', 'export_news.zip', true, 'news', 'export');

    Queue::assertPushed(ProcessTextFilterArchiveUpload::class, 2);
    Queue::assertPushed(ProcessTextFilterArchiveUpload::class, static fn (ProcessTextFilterArchiveUpload $job): bool => $job->category === 'movies');
    Queue::assertPushed(ProcessTextFilterArchiveUpload::class, static fn (ProcessTextFilterArchiveUpload $job): bool => $job->category === 'news');
});

test('rejects a category export containing another category before writing files', function () {
    $namespace = 'category_validation_test';
    $expectedCategory = 'validation_expected';
    $unexpectedCategory = 'validation_unexpected';
    $rulesManager = new FilterRulesManager();
    $expectedPath = $rulesManager->getRulesetPath($namespace, $expectedCategory, 'rules');
    $unexpectedPath = $rulesManager->getRulesetPath($namespace, $unexpectedCategory, 'rules');
    $archivePath = tempnam(sys_get_temp_dir(), 'filter-import-').'.zip';

    $zip = new ZipArchive();
    $zip->open($archivePath, ZipArchive::CREATE);
    $zip->addFromString($expectedCategory.'/domains.txt', "expected.example\n");
    $zip->addFromString($unexpectedCategory.'/domains.txt', "unexpected.example\n");
    $zip->close();

    try {
        expect(fn () => (new ProcessTextFilterArchiveUpload($namespace, $archivePath, true, $expectedCategory))
            ->processTextFilterArchive($namespace, $archivePath, true, $expectedCategory))
            ->toThrow(RuntimeException::class, 'expected [validation_expected] but contained [validation_expected, validation_unexpected]');

        expect(FilterList::count())->toBe(0)
            ->and(is_file($expectedPath))->toBeFalse()
            ->and(is_file($unexpectedPath))->toBeFalse();
    } finally {
        @unlink($archivePath);
        @unlink($expectedPath);
        @unlink($unexpectedPath);
        @rmdir(dirname($expectedPath));
        @rmdir(dirname(dirname($expectedPath)));
    }
});

test('removes every rule file for a category when a later input fails', function () {
    $namespace = 'durability_test';
    $category = 'failure';
    $rulesManager = new FilterRulesManager();
    $filtersPath = $rulesManager->getRulesetPath($namespace, $category, 'rules');
    $triggersPath = $rulesManager->getRulesetPath($namespace, $category, 'triggers');
    $archivePath = tempnam(sys_get_temp_dir(), 'filter-import-') . '.zip';
    $failureObservedAfterWrites = false;
    $filterUpdateCount = 0;

    $zip = new ZipArchive();
    $zip->open($archivePath, ZipArchive::CREATE);
    $zip->addFromString($category . '/domains.txt', "example.com\n");
    $zip->addFromString($category . '/urls.txt', "example.org\n");
    $zip->addFromString($category . '/triggers.txt', "example.net\n");
    $zip->close();

    FilterList::updating(function (FilterList $list) use (&$filterUpdateCount, &$failureObservedAfterWrites, $filtersPath, $triggersPath) {
        if ($list->type !== 'Filters') {
            return;
        }

        $filterUpdateCount++;

        if ($filterUpdateCount === 2) {
            $failureObservedAfterWrites = is_file($filtersPath) && is_file($triggersPath);
            throw new RuntimeException('forced failure while importing category');
        }
    });

    try {
        expect(fn () => (new ProcessTextFilterArchiveUpload($namespace, $archivePath, false))
            ->processTextFilterArchive($namespace, $archivePath, false, $category))
            ->toThrow(RuntimeException::class, 'forced failure while importing category');

        expect($failureObservedAfterWrites)->toBeTrue();
        expect(is_file($filtersPath))->toBeFalse();
        expect(is_file($triggersPath))->toBeFalse();
    } finally {
        @unlink($archivePath);
        @unlink($filtersPath);
        @unlink($triggersPath);
        @rmdir(dirname($filtersPath));
        @rmdir(dirname(dirname($filtersPath)));
    }
});
