<?php

use App\Jobs\ProcessTextFilterArchiveUpload;
use App\Jobs\RebuildGroupData;
use App\Models\FilterList;
use App\Models\FilterRulesManager;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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

test('all-category import skips disabled categories and adopts unknown categories', function () {
    $namespace = 'default';
    $disabledCategory = 'all_import_disabled';
    $unknownCategory = 'all_import_unknown';
    $rulesManager = new FilterRulesManager();
    $disabledPath = $rulesManager->getRulesetPath($namespace, $disabledCategory, 'rules');
    $unknownPath = $rulesManager->getRulesetPath($namespace, $unknownCategory, 'rules');
    $archivePath = tempnam(sys_get_temp_dir(), 'filter-import-').'.zip';

    file_put_contents($disabledPath, "existing disabled rule\n");
    FilterList::query()->create([
        'namespace' => $namespace,
        'category' => $disabledCategory,
        'type' => 'Filters',
        'import_enabled' => false,
    ]);

    $zip = new ZipArchive();
    $zip->open($archivePath, ZipArchive::CREATE);
    $zip->addFromString($disabledCategory.'/domains.txt', "disabled.example\n");
    $zip->addFromString($unknownCategory.'/domains.txt', "unknown.example\n");
    $zip->close();

    try {
        (new ProcessTextFilterArchiveUpload($namespace, $archivePath, true))
            ->processTextFilterArchive($namespace, $archivePath, true, null, true);

        expect(file_get_contents($disabledPath))->toBe("existing disabled rule\n")
            ->and(file_get_contents($unknownPath))->toBe("||unknown.example\n")
            ->and(FilterList::query()
                ->where('namespace', $namespace)
                ->where('category', $unknownCategory)
                ->where('type', 'Filters')
                ->exists())->toBeTrue();
    } finally {
        @unlink($archivePath);
        @unlink($disabledPath);
        @unlink($unknownPath);
        @rmdir(dirname($disabledPath));
        @rmdir(dirname(dirname($disabledPath)));
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

test('a standalone import (not part of a batch) debounces an immediate group rebuild', function () {
    config()->set('cache.default', 'array');
    Queue::fake();

    $namespace = 'standalone_batch_test';
    $category = 'standalone_category';
    $groupId = 9001;
    $rulesManager = new FilterRulesManager();
    $path = $rulesManager->getRulesetPath($namespace, $category, 'rules');
    $archivePath = tempnam(sys_get_temp_dir(), 'filter-import-').'.zip';

    $filterList = FilterList::query()->create([
        'namespace' => $namespace,
        'category' => $category,
        'type' => 'Filters',
    ]);
    DB::table('group_filter_assignments')->insert([
        'group_id' => $groupId,
        'filter_list_id' => $filterList->id,
    ]);

    $zip = new ZipArchive();
    $zip->open($archivePath, ZipArchive::CREATE);
    $zip->addFromString($category.'/domains.txt', "example.com\n");
    $zip->close();

    try {
        // A plain instance, dispatched neither via a job batch nor a queue: $this->batch() is
        // null, so this must fall back to debouncing the rebuild itself.
        (new ProcessTextFilterArchiveUpload($namespace, $archivePath, true, $category))
            ->processTextFilterArchive($namespace, $archivePath, true, $category);

        Queue::assertPushed(RebuildGroupData::class, static fn (RebuildGroupData $job): bool => $job->groupId === $groupId);
    } finally {
        @unlink($archivePath);
        @unlink($path);
        @rmdir(dirname($path));
        @rmdir(dirname(dirname($path)));
    }
});

test('an import running inside a batch skips the inline rebuild', function () {
    config()->set('cache.default', 'array');
    Queue::fake();

    $namespace = 'batched_import_test';
    $category = 'batched_category';
    $groupId = 9002;
    $rulesManager = new FilterRulesManager();
    $path = $rulesManager->getRulesetPath($namespace, $category, 'rules');
    $archivePath = tempnam(sys_get_temp_dir(), 'filter-import-').'.zip';

    $filterList = FilterList::query()->create([
        'namespace' => $namespace,
        'category' => $category,
        'type' => 'Filters',
    ]);
    DB::table('group_filter_assignments')->insert([
        'group_id' => $groupId,
        'filter_list_id' => $filterList->id,
    ]);

    $zip = new ZipArchive();
    $zip->open($archivePath, ZipArchive::CREATE);
    $zip->addFromString($category.'/domains.txt', "example.com\n");
    $zip->close();

    try {
        $job = new ProcessTextFilterArchiveUpload($namespace, $archivePath, true, $category);
        [$job] = $job->withFakeBatch();

        // Part of a (fake) batch: the batch's finally() callback is responsible for rebuilding
        // affected groups once the whole sweep finishes, so this must NOT dispatch its own rebuild.
        $job->processTextFilterArchive($namespace, $archivePath, true, $category);

        Queue::assertNotPushed(RebuildGroupData::class);
    } finally {
        @unlink($archivePath);
        @unlink($path);
        @rmdir(dirname($path));
        @rmdir(dirname(dirname($path)));
    }
});

test('getGroupsForCategories returns the union of groups assigned to the given categories', function () {
    $namespace = 'union_test';

    $moviesFilters = FilterList::query()->create(['namespace' => $namespace, 'category' => 'movies', 'type' => 'Filters']);
    $moviesTriggers = FilterList::query()->create(['namespace' => $namespace, 'category' => 'movies', 'type' => 'Triggers']);
    $newsFilters = FilterList::query()->create(['namespace' => $namespace, 'category' => 'news', 'type' => 'Filters']);
    $otherNamespaceFilters = FilterList::query()->create(['namespace' => 'other_namespace', 'category' => 'movies', 'type' => 'Filters']);

    DB::table('group_filter_assignments')->insert([
        ['group_id' => 1, 'filter_list_id' => $moviesFilters->id],
        ['group_id' => 2, 'filter_list_id' => $moviesTriggers->id], // same group as another category's row
        ['group_id' => 2, 'filter_list_id' => $newsFilters->id],    // duplicate group id across categories
        ['group_id' => 3, 'filter_list_id' => $otherNamespaceFilters->id], // different namespace: excluded
    ]);

    $groups = ProcessTextFilterArchiveUpload::getGroupsForCategories($namespace, ['movies', 'news']);

    expect($groups)->toEqualCanonicalizing([1, 2]);
});
