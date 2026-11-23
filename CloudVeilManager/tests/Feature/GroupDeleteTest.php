<?php

use App\Http\Middleware\CheckIfAdmin;
use App\Models\AppUserActivation;
use App\Models\Group;
use App\Models\GroupFilterAssignment;
use App\Models\User;
use App\Models\UserGroupToAppGroup;
use Backpack\CRUD\app\Http\Middleware\AuthenticateSession;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Users and activations have no foreign key to groups, so the admin delete has to refuse a group
// that is still in use rather than leave them pointing at a group that no longer exists. A group
// that can go takes its rule rows with it.

const GROUP_DELETE_TEST_SKIPPED_MIDDLEWARE = [CheckIfAdmin::class, AuthenticateSession::class];

const GROUP_DELETE_TEST_TABLES = [
    'group_filter_assignments', 'user_group_to_app_groups', 'app_user_activations', 'users', 'groups',
];

beforeEach(function (): void {
    foreach (GROUP_DELETE_TEST_TABLES as $table) {
        Schema::dropIfExists($table);
    }

    Schema::create('groups', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->text('app_cfg')->nullable();
        $table->timestamps();
    });

    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->string('password');
        $table->unsignedBigInteger('group_id');
        $table->timestamps();
    });

    Schema::create('app_user_activations', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->bigInteger('group_id');
        $table->string('identifier');
        $table->string('device_id');
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('group_filter_assignments', function (Blueprint $table) {
        $table->id();
        $table->bigInteger('group_id');
        $table->bigInteger('filter_list_id');
        $table->boolean('as_blacklist');
        $table->boolean('as_whitelist');
        $table->boolean('as_bypass');
        $table->timestamps();
    });

    // Production restricts deleting a group that still has rows here, so the test does too.
    Schema::create('user_group_to_app_groups', function (Blueprint $table) {
        $table->unsignedBigInteger('user_group_id');
        $table->unsignedBigInteger('app_group_id');
        $table->unsignedTinyInteger('filter_type');
        $table->foreign('user_group_id')->references('id')->on('groups');
    });

    $this->group = Group::forceCreate(['name' => 'Family']);
    $this->otherGroup = Group::forceCreate(['name' => 'School']);

    foreach ([$this->group, $this->otherGroup] as $group) {
        GroupFilterAssignment::forceCreate([
            'group_id' => $group->id, 'filter_list_id' => 1, 'as_blacklist' => 1, 'as_whitelist' => 0, 'as_bypass' => 0,
        ]);
        UserGroupToAppGroup::create(['user_group_id' => $group->id, 'app_group_id' => 1, 'filter_type' => 1]);
    }
});

afterEach(function (): void {
    foreach (GROUP_DELETE_TEST_TABLES as $table) {
        Schema::dropIfExists($table);
    }
});

function deleteGroup(Group $group)
{
    return test()->withoutMiddleware(GROUP_DELETE_TEST_SKIPPED_MIDDLEWARE)
        ->delete("/admin/group/{$group->id}");
}

function makeUser(Group $group): User
{
    return User::forceCreate([
        'name' => 'Parent', 'email' => 'parent@example.com', 'password' => 'x', 'group_id' => $group->id,
    ]);
}

function ruleRowCount(Group $group): int
{
    return GroupFilterAssignment::where('group_id', $group->id)->count()
        + UserGroupToAppGroup::where('user_group_id', $group->id)->count();
}

test('a group nobody uses is deleted along with its rules', function (): void {
    deleteGroup($this->group)->assertOk()->assertContent('1');

    expect(Group::find($this->group->id))->toBeNull()
        ->and(ruleRowCount($this->group))->toBe(0)
        ->and(ruleRowCount($this->otherGroup))->toBe(2);
});

test('a group with users is not deleted', function (): void {
    makeUser($this->group);
    makeUser($this->group);

    deleteGroup($this->group)
        ->assertOk()
        ->assertExactJson(['error' => ['This group is still used by 2 users. Move them to another group before deleting it.']]);

    expect(Group::find($this->group->id))->not->toBeNull()
        ->and(ruleRowCount($this->group))->toBe(2);
});

test('a group set as a device override is not deleted', function (): void {
    $user = makeUser($this->otherGroup);
    AppUserActivation::forceCreate([
        'user_id' => $user->id, 'group_id' => $this->group->id, 'identifier' => 'abc', 'device_id' => 'PC-1',
    ]);

    deleteGroup($this->group)
        ->assertOk()
        ->assertExactJson(['error' => ['This group is still used by 1 device. Move them to another group before deleting it.']]);

    expect(Group::find($this->group->id))->not->toBeNull();
});

test('a removed device does not keep its group from being deleted', function (): void {
    $user = makeUser($this->otherGroup);
    AppUserActivation::forceCreate([
        'user_id' => $user->id, 'group_id' => $this->group->id, 'identifier' => 'abc', 'device_id' => 'PC-1',
    ])->delete();

    deleteGroup($this->group)->assertOk()->assertContent('1');

    expect(Group::find($this->group->id))->toBeNull();
});
