<?php

use App\Client\FilteringPlainTextListModel;
use App\Client\PlainTextFilteringListType;
use App\Models\App;
use App\Models\AppGroup;
use App\Models\AppGroupToApp;
use App\Models\FilterList;
use App\Models\Group;
use App\Models\GroupFilterAssignment;
use App\Models\UserGroupToAppGroup;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Group::rebuildGroupData() compiles the config clients read through /me/config/*.

const GROUP_REBUILD_TEST_TABLES = [
    'user_group_to_app_groups', 'app_group_to_apps', 'app_groups', 'apps',
    'group_filter_assignments', 'filter_lists', 'groups',
];

const GROUP_REBUILD_TEST_GROUP_ID = 987654;

beforeEach(function (): void {
    foreach (GROUP_REBUILD_TEST_TABLES as $table) {
        Schema::dropIfExists($table);
    }

    Schema::create('groups', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->longText('config_cache')->nullable();
        $table->text('app_cfg')->nullable();
        $table->timestamps();
    });

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
        $table->boolean('as_blacklist')->default(false);
        $table->boolean('as_whitelist')->default(false);
        $table->boolean('as_bypass')->default(false);
        $table->timestamps();
    });

    Schema::create('apps', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('notes')->nullable();
        $table->string('platform_name');
        $table->timestamps();
    });

    Schema::create('app_groups', function (Blueprint $table) {
        $table->id();
        $table->string('group_name');
        $table->timestamps();
    });

    Schema::create('app_group_to_apps', function (Blueprint $table) {
        $table->unsignedBigInteger('app_group_id');
        $table->unsignedBigInteger('app_id');
    });

    Schema::create('user_group_to_app_groups', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_group_id');
        $table->unsignedBigInteger('app_group_id');
        $table->unsignedTinyInteger('filter_type')->default(0);
    });

    $this->group = Group::forceCreate([
        'id' => GROUP_REBUILD_TEST_GROUP_ID,
        'name' => 'Family',
        'app_cfg' => ['UpdateFrequency' => 5, 'Blocklist' => true],
    ]);

    $list = fn (string $category, string $type) => FilterList::forceCreate([
        'namespace' => 'default', 'category' => $category, 'type' => $type,
    ]);
    $assign = fn (FilterList $filterList, array $flags) => GroupFilterAssignment::create(
        ['group_id' => $this->group->id, 'filter_list_id' => $filterList->id] + $flags
    );

    $assign($list('gambling', 'Filters'), ['as_blacklist' => true]);
    $assign($list('news', 'Filters'), ['as_whitelist' => true]);
    $assign($list('social', 'Filters'), ['as_bypass' => true]);
    $assign($list('social', 'Triggers'), []);

    $games = AppGroup::create(['group_name' => 'Games']);
    $steam = App::create(['name' => 'steam.exe', 'platform_name' => 'WIN']);
    AppGroupToApp::create(['app_group_id' => $games->id, 'app_id' => $steam->id]);
    UserGroupToAppGroup::create([
        'user_group_id' => $this->group->id,
        'app_group_id' => $games->id,
        'filter_type' => UserGroupToAppGroup::FILTER_TYPE_BLOCK_APPS,
    ]);
});

afterEach(function (): void {
    foreach (GROUP_REBUILD_TEST_TABLES as $table) {
        Schema::dropIfExists($table);
    }
});

test('rebuilding a group compiles its lists, apps and settings into config_cache', function (): void {
    $this->group->rebuildGroupData();

    $config = json_decode($this->group->fresh()->config_cache, true);

    expect($config['ConfiguredLists'])->toBe(json_decode(json_encode([
        new FilteringPlainTextListModel(PlainTextFilteringListType::Blacklist, '/default/gambling/rules.txt'),
        new FilteringPlainTextListModel(PlainTextFilteringListType::Whitelist, '/default/news/rules.txt'),
        new FilteringPlainTextListModel(PlainTextFilteringListType::BypassList, '/default/social/rules.txt'),
        new FilteringPlainTextListModel(PlainTextFilteringListType::TextTrigger, '/default/social/triggers.txt'),
    ]), true))
        ->and($config['BlockedApplications'])->toBe([['name' => 'steam.exe', 'os' => 'WIN']])
        ->and($config['WhitelistedApplications'])->toBe([])
        ->and($config['BlacklistedApplications'])->toBe([])
        ->and($config['UpdateFrequency'])->toBe(5)
        ->and($config)->not->toHaveKey('Blocklist');
});

test('rebuilding a group does not write a zip payload', function (): void {
    $this->group->rebuildGroupData();

    expect(is_dir(storage_path('group_data/'.GROUP_REBUILD_TEST_GROUP_ID)))->toBeFalse();
});

test('the legacy user data endpoints no longer exist', function (string $uri): void {
    $this->post($uri)->assertNotFound();
})->with([
    '/api/v2/me/data/check',
    '/api/v2/me/data/get',
    '/api/user/me/data/check',
    '/api/user/me/data/get',
]);
