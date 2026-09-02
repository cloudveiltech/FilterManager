<?php

use App\Jobs\ProcessTextFilterArchiveUpload;
use App\Models\FilterList;
use App\Models\FilterRulesManager;
use Illuminate\Database\Schema\Blueprint;
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
            ->processTextFilterArchive($namespace, $archivePath, false))
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
