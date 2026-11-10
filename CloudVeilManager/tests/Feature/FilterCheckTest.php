<?php

use App\Client\PlainTextFilteringListType;
use App\Http\Middleware\AuthenticateOnceWithBasicAuth;
use App\Http\Middleware\CheckIfAdmin;
use App\Models\AppUserActivation;
use App\Models\FilterRulesManager;
use App\Models\Group;
use App\Models\User;
use App\Services\FilterCheck\AbpRuleMatcher;
use App\Services\FilterCheck\FilterChecker;
use App\Services\FilterCheck\FilterTarget;
use Backpack\CRUD\app\Http\Middleware\AuthenticateSession;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Zizaco\Entrust\Middleware\EntrustRole;

const FILTER_CHECK_TEST_TABLES = ['app_user_activations', 'users', 'groups'];

beforeEach(function () {
    foreach (FILTER_CHECK_TEST_TABLES as $table) {
        Schema::dropIfExists($table);
    }

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
        $table->string('friendly_name')->nullable();
        $table->text('config_override')->nullable();
        $table->boolean('debug_enabled')->default(false);
        $table->integer('bypass_quantity')->nullable();
        $table->integer('bypass_period')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    // List files in a temporary folder instead of resources/rules.
    $this->rulesDir = sys_get_temp_dir().'/cloudveil-filter-check-'.uniqid();
    File::makeDirectory($this->rulesDir);
    $dir = $this->rulesDir;
    app()->instance(FilterRulesManager::class, new class($dir) extends FilterRulesManager
    {
        public function __construct(private string $dir) {}

        public function rulesetFilePath(string $namespace, string $category, string $type): string
        {
            return "{$this->dir}/{$category}.{$type}.txt";
        }
    });

    $this->writeList = fn (string $category, array $lines) => file_put_contents("{$dir}/{$category}.rules.txt", implode("\n", $lines)."\n");

    ($this->writeList)('adult', ['! adult sites', '||adult.example', '||mixed.example^adult']);
    ($this->writeList)('gambling', ['||casino.example', '||mixed.example']);
    ($this->writeList)('news', ['||news.example', '||mixed.example^news']);
    ($this->writeList)('games', ['||games.example']);
    ($this->writeList)('exceptions', ['@@||mixed.example^adult^ok']);

    $list = fn (string $type, string $category) => ['ListType' => $type, 'RelativeListPath' => "/default/{$category}/rules.txt"];

    $this->group = Group::forceCreate([
        'name' => 'Family',
        'config_cache' => json_encode([
            'ConfiguredLists' => [
                $list(PlainTextFilteringListType::Blacklist, 'adult'),
                $list(PlainTextFilteringListType::Blacklist, 'gambling'),
                $list(PlainTextFilteringListType::Blacklist, 'exceptions'),
                $list(PlainTextFilteringListType::Whitelist, 'news'),
                $list(PlainTextFilteringListType::BypassList, 'games'),
                $list(PlainTextFilteringListType::Blacklist, 'missing'),
                $list(PlainTextFilteringListType::TextTrigger, 'adult'),
            ],
            'BypassesPermitted' => 3,
            'BypassDuration' => 30,
        ]),
    ]);

    $this->user = User::forceCreate([
        'name' => 'Parent',
        'email' => 'parent@example.test',
        'password' => 'x',
        'group_id' => $this->group->id,
        'config_override' => ['SelfModeration' => ['social.example/feed']],
    ]);

    $this->activation = AppUserActivation::forceCreate([
        'user_id' => $this->user->id,
        'identifier' => 'device-1',
        'device_id' => 'Kids-Laptop',
        'config_override' => ['CustomWhitelist' => ['casino.example']],
    ]);

    $this->check = fn (array $urls, ?FilterTarget $target = null, bool $bypass = false) => app(FilterChecker::class)
        ->check($target ?? new FilterTarget(FilterTarget::GROUP, $this->group), $urls, $bypass);
});

afterEach(function () {
    File::deleteDirectory($this->rulesDir);
    foreach (FILTER_CHECK_TEST_TABLES as $table) {
        Schema::dropIfExists($table);
    }
});

test('a URL on a block list is blocked, naming the list and rule', function () {
    $result = ($this->check)(['https://www.adult.example/videos'])['results'][0];

    expect($result['blocked'])->toBeTrue()
        ->and($result['verdict'])->toBe('blocked')
        ->and($result['decided_by'])->toMatchArray([
            'list' => '/default/adult/rules.txt',
            'category' => 'adult',
            'list_type' => 'Blacklist',
            'source' => 'group',
            'effect' => 'block',
            'rule' => '||adult.example',
        ])
        ->and($result['blocking_categories'])->toBe(['adult'])
        ->and($result['mac'])->toBe(['blocked' => true, 'differs' => false]);
});

test('URLs without a scheme or path are checked as https with a path', function () {
    $result = ($this->check)(['Casino.Example?x=1'])['results'][0];

    expect($result['checked_url'])->toBe('https://casino.example/?x=1')
        ->and($result['blocked'])->toBeTrue();
});

test('an allow list wins, and the categories that would block are still listed', function () {
    $result = ($this->check)(['https://mixed.example/news/today'])['results'][0];

    expect($result['blocked'])->toBeFalse()
        ->and($result['decided_by']['category'])->toBe('news')
        ->and($result['blocking_categories'])->toBe(['gambling'])
        ->and(collect($result['matches'])->pluck('category')->all())->toBe(['gambling', 'news']);
});

test('a host allowed outright passes through without checking paths', function () {
    ($this->writeList)('news', ['||news.example']);
    ($this->writeList)('adult', ['||news.example^adult']);

    $result = ($this->check)(['https://news.example/adult/page'])['results'][0];

    expect($result['blocked'])->toBeFalse()
        ->and($result['host_passthrough'])->toBeTrue()
        ->and($result['blocking_categories'])->toBe(['adult']);
});

test('an @@ rule in a later block list allows on Windows but not on Mac', function () {
    $result = ($this->check)(['https://mixed.example/adult/ok'])['results'][0];

    expect($result['blocked'])->toBeFalse()
        ->and($result['decided_by']['category'])->toBe('exceptions')
        ->and($result['blocking_categories'])->toBe(['adult', 'gambling'])
        ->and($result['mac'])->toBe(['blocked' => true, 'differs' => true]);
});

test('bypass lists block only when nothing else matched, and not while bypass is active', function () {
    expect(($this->check)(['https://games.example/'])['results'][0])
        ->toMatchArray(['blocked' => true, 'verdict' => 'blocked_bypassable'])
        ->and(($this->check)(['https://games.example/'], bypass: true)['results'][0])
        ->toMatchArray(['blocked' => false, 'verdict' => 'allowed']);
});

test('an activation check includes the user and device custom lists', function () {
    $target = new FilterTarget(FilterTarget::ACTIVATION, $this->group, $this->user, $this->activation);
    [$casino, $feed] = ($this->check)(['https://casino.example/', 'https://social.example/feed/1'], $target)['results'];

    expect($casino['blocked'])->toBeFalse()
        ->and($casino['decided_by'])->toMatchArray(['source' => 'custom', 'category' => 'custom_whitelist'])
        // Windows uses custom entries as written; the Mac adds || and can't match a path.
        ->and($feed['blocked'])->toBeTrue()
        ->and($feed['decided_by'])->toMatchArray(['category' => 'self_moderation', 'rule' => 'social.example/feed'])
        ->and($feed['mac'])->toBe(['blocked' => false, 'differs' => true]);
});

test('rules without a domain anchor, or with capitals, are still found', function () {
    ($this->writeList)('adult', ['||adult.example', '/tracker/*', '  ||Spaced.Example']);

    [$tracker, $spaced, $neither] = ($this->check)(['https://cdn.example/tracker/x.js', 'https://spaced.example/', 'https://cdn.example/ok'])['results'];

    expect($tracker['decided_by'])->toMatchArray(['category' => 'adult', 'rule' => '/tracker/*'])
        ->and($spaced['decided_by'])->toMatchArray(['category' => 'adult', 'rule' => '||spaced.example'])
        ->and($neither['blocked'])->toBeFalse();
});

test('a URL on no list is allowed, and missing list files are reported', function () {
    $response = ($this->check)(['https://example.org/']);

    expect($response['results'][0])->toMatchArray(['blocked' => false, 'decided_by' => null, 'matches' => []])
        ->and($response['notes'])->toContain('The list file for /default/missing/rules.txt is missing on this server, so it was not checked.');
});

test('a rule too complex to match is reported for that URL, and the other URLs are still checked', function () {
    app()->instance(AbpRuleMatcher::class, new AbpRuleMatcher(maxSteps: 50));
    ($this->writeList)('adult', ['||adult.example', '*a*a*a*a*a*a*a*a*a*a*a*a*zzz']);

    [$slow, $fine] = ($this->check)(['https://example.org/'.str_repeat('a', 80), 'https://adult.example/'])['results'];

    expect($slow)->toBe([
        'url' => 'https://example.org/'.str_repeat('a', 80),
        'error' => 'A rule is too complex to check: *a*a*a*a*a*a*a*a*a*a*a*a*zzz (adult list).',
    ])->and($fine['decided_by'])->toMatchArray(['category' => 'adult', 'rule' => '||adult.example']);
});

test('time restrictions are not part of the check', function () {
    $this->group->forceFill(['config_cache' => json_encode([
        ...json_decode($this->group->config_cache, true),
        'TimeRestrictions' => array_fill_keys(['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'], ['RestrictionsEnabled' => true, 'EnabledThrough' => ['00.00', '00.00']]),
    ])])->save();

    $response = ($this->check)(['https://example.org/']);

    expect($response)->not->toHaveKey('time_restriction')
        ->and($response['results'][0]['blocked'])->toBeFalse();
});

test('the manage endpoint checks an activation by id', function () {
    $this->withoutMiddleware([AuthenticateOnceWithBasicAuth::class, EntrustRole::class])
        ->postJson('/api/manage/filter-check', [
            'activation_id' => $this->activation->id,
            'urls' => ['https://casino.example/', 'https://adult.example/'],
        ])
        ->assertOk()
        ->assertJsonPath('target.type', 'activation')
        ->assertJsonPath('target.activation_id', $this->activation->id)
        ->assertJsonPath('target.group.name', 'Family')
        ->assertJsonPath('results.0.blocked', false)
        ->assertJsonPath('results.1.blocked', true)
        ->assertJsonPath('results.1.blocking_categories', ['adult']);
});

test('the manage endpoint uses the device group, then the user group, then the default group', function () {
    $other = Group::forceCreate(['name' => 'Device Group', 'config_cache' => json_encode(['ConfiguredLists' => []])]);
    config(['filter_check.default_group_id' => $other->id]);
    $api = $this->withoutMiddleware([AuthenticateOnceWithBasicAuth::class, EntrustRole::class]);

    $api->postJson('/api/manage/filter-check', ['email' => 'parent@example.test', 'url' => 'adult.example'])
        ->assertOk()
        ->assertJsonPath('target.type', 'user')
        ->assertJsonPath('target.group.name', 'Family');

    $api->postJson('/api/manage/filter-check', ['url' => 'adult.example'])
        ->assertOk()
        ->assertJsonPath('target.type', 'default_group')
        ->assertJsonPath('target.group.name', 'Device Group')
        ->assertJsonPath('results.0.blocked', false);

    $this->activation->forceFill(['group_id' => $other->id])->save();
    $api->postJson('/api/manage/filter-check', ['identifier' => 'device-1', 'url' => 'adult.example'])
        ->assertOk()
        ->assertJsonPath('target.group.name', 'Device Group');
});

test('the manage endpoint rejects more than one target and missing targets', function () {
    $api = $this->withoutMiddleware([AuthenticateOnceWithBasicAuth::class, EntrustRole::class]);

    $api->postJson('/api/manage/filter-check', ['url' => 'a.example', 'user_id' => $this->user->id, 'group_id' => $this->group->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('group_id');

    $api->postJson('/api/manage/filter-check', ['url' => 'a.example', 'activation_id' => 999])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('activation_id');

    $api->postJson('/api/manage/filter-check', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['url', 'urls']);
});

test('the manage endpoint needs credentials', function () {
    $this->postJson('/api/manage/filter-check', ['url' => 'adult.example'])->assertUnauthorized();
});

test('the admin page shows the verdict and the lists that matched', function () {
    $this->withoutVite()
        ->withoutMiddleware([CheckIfAdmin::class, AuthenticateSession::class])
        ->get('/admin/filter-check?'.http_build_query([
            'target' => 'group_id',
            'target_id' => $this->group->id,
            'urls' => "adult.example\nmixed.example/news/today",
        ]))
        ->assertOk()
        ->assertSeeInOrder(['https://adult.example/', 'Blocked', 'Decided by', 'adult', '||adult.example'])
        ->assertSeeInOrder(['https://mixed.example/news/today', 'Allowed', 'gambling', 'block', 'news', 'allow']);
});

test('the admin page shows target errors on the page', function () {
    $this->withoutVite()
        ->withoutMiddleware([CheckIfAdmin::class, AuthenticateSession::class])
        ->get('/admin/filter-check?'.http_build_query(['target' => 'user_id', 'target_id' => 999, 'urls' => 'adult.example']))
        ->assertOk()
        ->assertSee('No user was found.');
});
