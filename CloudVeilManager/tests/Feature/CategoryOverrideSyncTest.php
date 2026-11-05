<?php

use App\Client\FilteringPlainTextListModel;
use App\Client\PlainTextFilteringListType;
use App\Models\AppUserActivation;
use App\Models\FilterList;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\AccessToken;
use Laravel\Passport\Passport;

// Category overrides as a filter client sees them: set on the user/activation, then read back
// through the device sync endpoints (/me/config/get and /me/config/check).

beforeEach(function () {
    foreach (['app_user_activations', 'users', 'groups', 'filter_lists'] as $table) {
        Schema::dropIfExists($table);
    }

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

    Schema::create('groups', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->longText('config_cache')->nullable();
        $table->text('app_cfg')->nullable();
        $table->string('data_sha1')->nullable();
        $table->timestamps();
    });

    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->string('password');
        $table->unsignedBigInteger('group_id')->nullable();
        $table->text('config_override')->nullable();
        $table->boolean('enable_relaxed_policy_passcode')->default(false);
        $table->rememberToken();
        $table->timestamps();
    });

    Schema::create('app_user_activations', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->unsignedBigInteger('group_id')->nullable();
        $table->string('identifier');
        $table->string('device_id');
        $table->string('ip_address')->nullable();
        $table->string('app_version')->nullable();
        $table->string('os_version')->nullable();
        $table->string('platform_name')->nullable();
        $table->string('friendly_name')->nullable();
        $table->text('config_override')->nullable();
        $table->boolean('debug_enabled')->default(false);
        $table->integer('bypass_quantity')->nullable();
        $table->integer('bypass_period')->nullable();
        $table->integer('bypass_used')->default(0);
        $table->boolean('banned')->default(false);
        $table->string('token_id')->nullable();
        $table->timestamp('last_sync_time')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    $list = fn (string $category, string $type) => FilterList::forceCreate([
        'namespace' => 'default', 'category' => $category, 'type' => $type,
    ]);
    $this->porn = $list('adult_pornography', 'Filters');
    $this->gambling = $list('gambling', 'Filters');
    $this->news = $list('news', 'Filters');
    $this->social = $list('social', 'Filters');
    $this->socialTriggers = $list('social', 'Triggers');

    // The group's compiled config, as Group::rebuildGroupData() caches it.
    $this->group = Group::forceCreate([
        'name' => 'Family',
        'config_cache' => json_encode([
            'ConfiguredLists' => [
                new FilteringPlainTextListModel(PlainTextFilteringListType::Blacklist, $this->porn->getRelativeListPath()),
                new FilteringPlainTextListModel(PlainTextFilteringListType::Blacklist, $this->gambling->getRelativeListPath()),
                new FilteringPlainTextListModel(PlainTextFilteringListType::Whitelist, $this->news->getRelativeListPath()),
            ],
            'BlacklistedApplications' => [],
            'WhitelistedApplications' => [],
            'BlockedApplications' => [],
        ]),
    ]);

    $this->user = User::forceCreate([
        'name' => 'Parent',
        'email' => 'parent@example.test',
        'password' => 'x',
        'group_id' => $this->group->id,
    ]);

    $this->activation = AppUserActivation::forceCreate([
        'user_id' => $this->user->id,
        'identifier' => 'device-1',
        'device_id' => 'Kids-Laptop',
        'platform_name' => 'WIN',
    ]);

    // The api guard builds Passport's resource server, which needs a key pair even when
    // actingAs() skips the token check.
    $keys = sys_get_temp_dir().'/cloudveil-test-passport-keys';
    if (!file_exists($keys.'/oauth-public.key')) {
        @mkdir($keys, 0700, true);
        $key = openssl_pkey_new(['private_key_bits' => 2048]);
        openssl_pkey_export($key, $private);
        file_put_contents($keys.'/oauth-private.key', $private);
        file_put_contents($keys.'/oauth-public.key', openssl_pkey_get_details($key)['key']);
        chmod($keys.'/oauth-private.key', 0600);
        chmod($keys.'/oauth-public.key', 0600);
    }
    Passport::loadKeysFrom($keys);

    // A token with an id, like a real device token: the sync records it on the activation.
    Passport::actingAs($this->user)->withAccessToken(new AccessToken([
        'id' => 'device-token',
        'oauth_user_id' => $this->user->id,
        'oauth_scopes' => [],
    ]));
});

afterEach(function () {
    foreach (['app_user_activations', 'users', 'groups', 'filter_lists'] as $table) {
        Schema::dropIfExists($table);
    }
});

function syncConfig($test): array
{
    $device = ['identifier' => 'device-1', 'device_id' => 'Kids-Laptop'];

    $get = $test->postJson('/api/v2/me/config/get', $device)->assertOk();
    $check = $test->postJson('/api/v2/me/config/check', $device)->assertOk();

    return [$get->json(), $get->getContent(), $check->json('sha1')];
}

// RelativeListPath => ListType, for comparing ConfiguredLists regardless of order.
function listTypesByPath(array $configuredLists): array
{
    return collect($configuredLists)->pluck('ListType', 'RelativeListPath')->sortKeys()->all();
}

function expectClientShape(array $config, string $body, string $sha1): void
{
    expect($config)->not->toHaveKey('CategoryOverrides')
        ->and($body)->toContain('"ConfiguredLists":[')
        ->and($config['ConfiguredLists'])->toBeList()
        ->and($sha1)->toBe(sha1($body));

    foreach ($config['ConfiguredLists'] as $entry) {
        expect($entry)->toHaveKeys(['ListType', 'RelativeListPath'])->toHaveCount(2)
            ->and($entry['ListType'])->toBeIn(['Blacklist', 'Whitelist', 'BypassList', 'TextTrigger']);
    }
}

test('a device without overrides gets the group lists unchanged', function () {
    [$config, $body, $sha1] = syncConfig($this);

    expectClientShape($config, $body, $sha1);
    expect(listTypesByPath($config['ConfiguredLists']))->toBe([
        '/default/adult_pornography/rules.txt' => 'Blacklist',
        '/default/gambling/rules.txt' => 'Blacklist',
        '/default/news/rules.txt' => 'Whitelist',
    ]);
});

test('user and activation overrides change, add and remove categories in the device config', function () {
    [, , $sha1Before] = syncConfig($this);

    $this->user->category_overrides = [
        $this->news->id => 'Blacklist',           // change a group category
        $this->social->id => 'Whitelist',         // the activation overrides this one
    ];
    $this->user->save();

    $this->activation->category_overrides = [
        $this->social->id => 'BypassList',        // add a category the group doesn't have
        $this->socialTriggers->id => 'TextTrigger', // add a trigger list
        $this->gambling->id => 'Ignored',         // remove a group category
    ];
    $this->activation->save();

    [$config, $body, $sha1] = syncConfig($this);

    expectClientShape($config, $body, $sha1);
    expect(listTypesByPath($config['ConfiguredLists']))->toBe([
        '/default/adult_pornography/rules.txt' => 'Blacklist',
        '/default/news/rules.txt' => 'Blacklist',
        '/default/social/rules.txt' => 'BypassList',
        '/default/social/triggers.txt' => 'TextTrigger',
    ])->and($sha1)->not->toBe($sha1Before);
});

test('overrides that do not fit the category type or point at no category are not saved', function () {
    $this->activation->category_overrides = [
        $this->socialTriggers->id => 'Blacklist', // trigger lists can only be TextTrigger
        $this->social->id => 'TextTrigger',       // filter lists can't be TextTrigger
        999 => 'Blacklist',                       // no such category
        $this->news->id => 'Ignored',
    ];
    $this->activation->save();

    expect($this->activation->fresh()->config_override['CategoryOverrides'])->toBe([
        ['categoryId' => $this->news->id, 'override' => 'Ignored'],
    ]);

    [$config] = syncConfig($this);
    expect(listTypesByPath($config['ConfiguredLists']))->toBe([
        '/default/adult_pornography/rules.txt' => 'Blacklist',
        '/default/gambling/rules.txt' => 'Blacklist',
    ]);
});
