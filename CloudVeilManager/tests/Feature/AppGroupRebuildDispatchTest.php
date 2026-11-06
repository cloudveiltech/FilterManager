<?php

use App\Http\Middleware\CheckIfAdmin;
use App\Jobs\RebuildGroupData;
use App\Models\App;
use App\Models\AppGroup;
use App\Models\AppGroupToApp;
use App\Models\UserGroupToAppGroup;
use Backpack\CRUD\app\Http\Middleware\AuthenticateSession;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Schema;

// Only skip the admin auth checks. Backpack sets up the CRUD panel in controller middleware,
// so a blanket withoutMiddleware() would break the controllers under test.
const APP_REBUILD_TEST_SKIPPED_MIDDLEWARE = [CheckIfAdmin::class, AuthenticateSession::class];

const APP_REBUILD_TEST_TABLES = ['user_group_to_app_groups', 'app_group_to_apps', 'app_groups', 'apps'];

beforeEach(function (): void {
    foreach (APP_REBUILD_TEST_TABLES as $table) {
        Schema::dropIfExists($table);
    }

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

    // Two app groups used by different user groups, plus one unrelated user group.
    $this->browsers = AppGroup::create(['group_name' => 'Browsers']);
    $this->games = AppGroup::create(['group_name' => 'Games']);
    $this->unrelated = AppGroup::create(['group_name' => 'Unrelated']);

    UserGroupToAppGroup::create(['user_group_id' => 10, 'app_group_id' => $this->browsers->id]);
    UserGroupToAppGroup::create(['user_group_id' => 11, 'app_group_id' => $this->browsers->id]);
    UserGroupToAppGroup::create(['user_group_id' => 20, 'app_group_id' => $this->games->id]);
    UserGroupToAppGroup::create(['user_group_id' => 99, 'app_group_id' => $this->unrelated->id]);
});

afterEach(function (): void {
    foreach (APP_REBUILD_TEST_TABLES as $table) {
        Schema::dropIfExists($table);
    }
});

function assertRebuildQueuedFor(array $expectedGroupIds): void
{
    $actual = Bus::dispatched(RebuildGroupData::class)
        ->map(static fn (RebuildGroupData $job): int => $job->groupId)
        ->sort()
        ->values()
        ->all();
    sort($expectedGroupIds);

    expect($actual)->toBe($expectedGroupIds);
}

test('saving the same app twice while rebuilds are pending only queues one rebuild per group', function (): void {
    Bus::fake();

    $app = App::create(['name' => 'Firefox', 'platform_name' => 'WIN']);
    AppGroupToApp::create(['app_group_id' => $this->browsers->id, 'app_id' => $app->id]);

    foreach (['Firefox', 'Firefox ESR'] as $name) {
        $this->withoutMiddleware(APP_REBUILD_TEST_SKIPPED_MIDDLEWARE)
            ->put("/admin/app/{$app->id}", [
                'id' => $app->id,
                'name' => $name,
                'platform_name' => 'WIN',
                'group' => [$this->browsers->id],
            ])
            ->assertRedirect();
    }

    assertRebuildQueuedFor([10, 11]);
});

test('a legacy multi-group job fans out into deduplicated per-group jobs', function (): void {
    Bus::fake();

    // Simulate a payload queued before the deploy: groupIds set, no groupId.
    $legacy = (new ReflectionClass(RebuildGroupData::class))->newInstanceWithoutConstructor();
    $legacy->groupIds = [10, 11, 11, 20];

    // A per-group rebuild for 10 is already waiting, so the fan-out should skip it.
    RebuildGroupData::dispatch(10);
    $legacy->handle();

    assertRebuildQueuedFor([10, 11, 20]);
});

test('creating an app queues a rebuild for the user groups using its app groups', function (): void {
    Bus::fake();

    $this->withoutMiddleware(APP_REBUILD_TEST_SKIPPED_MIDDLEWARE)
        ->post('/admin/app', [
            'name' => 'Firefox',
            'platform_name' => 'WIN',
            'group' => [$this->browsers->id],
        ])
        ->assertRedirect();

    assertRebuildQueuedFor([10, 11]);
});

test('updating an app queues a rebuild for both its old and new app groups', function (): void {
    Bus::fake();

    $app = App::create(['name' => 'Firefox', 'platform_name' => 'WIN']);
    AppGroupToApp::create(['app_group_id' => $this->browsers->id, 'app_id' => $app->id]);

    $this->withoutMiddleware(APP_REBUILD_TEST_SKIPPED_MIDDLEWARE)
        ->put("/admin/app/{$app->id}", [
            'id' => $app->id,
            'name' => 'Firefox',
            'platform_name' => 'WIN',
            'group' => [$this->games->id],
        ])
        ->assertRedirect();

    assertRebuildQueuedFor([10, 11, 20]);
});

test('deleting an app queues a rebuild for the user groups it was in', function (): void {
    Bus::fake();

    $app = App::create(['name' => 'Firefox', 'platform_name' => 'WIN']);
    AppGroupToApp::create(['app_group_id' => $this->games->id, 'app_id' => $app->id]);

    $this->withoutMiddleware(APP_REBUILD_TEST_SKIPPED_MIDDLEWARE)
        ->delete("/admin/app/{$app->id}")
        ->assertOk();

    assertRebuildQueuedFor([20]);
    expect(App::find($app->id))->toBeNull();
});

test('saving an app in no app groups does not queue a rebuild', function (): void {
    Bus::fake();

    $this->withoutMiddleware(APP_REBUILD_TEST_SKIPPED_MIDDLEWARE)
        ->post('/admin/app', [
            'name' => 'Notepad',
            'platform_name' => 'WIN',
        ])
        ->assertRedirect();

    Bus::assertNotDispatched(RebuildGroupData::class);
});

test('updating an app group queues a rebuild for the user groups using it', function (): void {
    Bus::fake();

    $this->withoutMiddleware(APP_REBUILD_TEST_SKIPPED_MIDDLEWARE)
        ->put("/admin/app-group/{$this->browsers->id}", [
            'id' => $this->browsers->id,
            'group_name' => 'Web Browsers',
        ])
        ->assertRedirect();

    assertRebuildQueuedFor([10, 11]);
});
