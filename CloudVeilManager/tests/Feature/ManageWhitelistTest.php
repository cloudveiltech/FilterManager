<?php

use App\Models\AppUserActivation;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// POST /api/manage/whitelist: Manage editing a user's or an activation's CustomWhitelist.

beforeEach(function () {
    // Auth (basic auth + admin role) and the live-DB check are the manage group's concern, not this endpoint's.
    $this->withoutMiddleware([
        \App\Http\Middleware\checkDBLive::class,
        \App\Http\Middleware\AuthenticateOnceWithBasicAuth::class,
        \Zizaco\Entrust\Middleware\EntrustRole::class,
    ]);

    foreach (['app_user_activations', 'users'] as $table) {
        Schema::dropIfExists($table);
    }

    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->string('password');
        $table->unsignedBigInteger('group_id')->nullable();
        $table->text('config_override')->nullable();
        $table->rememberToken();
        $table->timestamps();
    });

    Schema::create('app_user_activations', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->string('identifier');
        $table->string('device_id');
        $table->text('config_override')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    $this->user = User::forceCreate([
        'name' => 'Parent',
        'email' => 'parent@example.test',
        'password' => 'x',
        'config_override' => ['CustomWhitelist' => ['khanacademy.org'], 'SelfModeration' => ['games.example']],
    ]);

    $this->activation = AppUserActivation::forceCreate([
        'user_id' => $this->user->id,
        'identifier' => 'device-1',
        'device_id' => 'Kids-Laptop',
    ]);
});

afterEach(function () {
    foreach (['app_user_activations', 'users'] as $table) {
        Schema::dropIfExists($table);
    }
});

it('adds and removes domains on a user matched by email, keeping other config', function () {
    $this->postJson('/api/manage/whitelist', [
        'email' => 'parent@example.test',
        'add' => ['https://Wikipedia.org/wiki/Main_Page', 'KhanAcademy.org', 'www.example.com:8080/a?b=c', 'wikipedia.org'],
        'remove' => ['nothing-here.example'],
    ])->assertOk()->assertExactJson([
        'type' => 'user',
        'id' => $this->user->id,
        'email' => 'parent@example.test',
        'whitelist' => ['khanacademy.org', 'wikipedia.org', 'www.example.com'],
    ]);

    $this->postJson('/api/manage/whitelist', [
        'email' => 'parent@example.test',
        'remove' => ['http://khanacademy.org/'],
    ])->assertOk()->assertJsonPath('whitelist', ['wikipedia.org', 'www.example.com']);

    expect($this->user->fresh()->config_override)->toBe([
        'CustomWhitelist' => ['wikipedia.org', 'www.example.com'],
        'SelfModeration' => ['games.example'],
    ]);
});

it('matches stored entries by domain, but only a whole-domain entry blocks an add', function () {
    $this->user->forceFill(['config_override' => ['CustomWhitelist' => [
        "xboxlive.com\t", '.cloudfront.net', 'www.facebook.com/marketplace/',
    ]]])->save();

    $this->postJson('/api/manage/whitelist', [
        'email' => 'parent@example.test',
        'add' => ['xboxlive.com', 'cloudfront.net', 'www.facebook.com'],
    ])->assertOk()->assertJsonPath('whitelist', [
        "xboxlive.com\t", '.cloudfront.net', 'www.facebook.com/marketplace/', 'www.facebook.com',
    ]);

    $this->postJson('/api/manage/whitelist', [
        'email' => 'parent@example.test',
        'remove' => ['www.facebook.com', 'XBOXLIVE.COM'],
    ])->assertOk()->assertJsonPath('whitelist', ['.cloudfront.net']);
});

it('replaces an activation whitelist matched by identifier, and clears it with an empty list', function () {
    $this->postJson('/api/manage/whitelist', [
        'identifier' => 'device-1',
        'domains' => ['school.example', 'https://School.example/login', 'sub.school.example'],
    ])->assertOk()->assertExactJson([
        'type' => 'activation',
        'id' => $this->activation->id,
        'identifier' => 'device-1',
        'email' => 'parent@example.test',
        'whitelist' => ['school.example', 'sub.school.example'],
    ]);

    expect($this->activation->fresh()->config_override)->toBe(['CustomWhitelist' => ['school.example', 'sub.school.example']]);
    // The user's own list is untouched.
    expect($this->user->fresh()->config_override['CustomWhitelist'])->toBe(['khanacademy.org']);

    $this->postJson('/api/manage/whitelist', ['identifier' => 'device-1', 'domains' => []])
        ->assertOk()->assertJsonPath('whitelist', []);

    expect($this->activation->fresh()->config_override)->toBe([]);
});

it('needs the email to pick between activations sharing an identifier', function () {
    $other = User::forceCreate(['name' => 'Other', 'email' => 'other@example.test', 'password' => 'x']);
    AppUserActivation::forceCreate(['user_id' => $other->id, 'identifier' => 'device-1', 'device_id' => 'PC']);

    $this->postJson('/api/manage/whitelist', ['identifier' => 'device-1', 'add' => ['a.example']])
        ->assertStatus(409);

    $this->postJson('/api/manage/whitelist', [
        'identifier' => 'device-1',
        'email' => 'parent@example.test',
        'add' => ['a.example'],
    ])->assertOk()->assertJsonPath('id', $this->activation->id);
});

it('returns 404 when nothing matches', function () {
    $this->postJson('/api/manage/whitelist', ['email' => 'nobody@example.test', 'add' => ['a.example']])
        ->assertNotFound();
    $this->postJson('/api/manage/whitelist', ['identifier' => 'missing', 'add' => ['a.example']])
        ->assertNotFound();
});

it('rejects requests without a target, without a change, or mixing replace with add/remove', function () {
    $this->postJson('/api/manage/whitelist', ['add' => ['a.example']])
        ->assertJsonValidationErrors('email');
    $this->postJson('/api/manage/whitelist', ['email' => 'parent@example.test'])
        ->assertJsonValidationErrors('domains');
    $this->postJson('/api/manage/whitelist', ['email' => 'parent@example.test', 'domains' => [], 'add' => ['a.example']])
        ->assertJsonValidationErrors('domains');
    $this->postJson('/api/manage/whitelist', ['email' => 'parent@example.test', 'add' => ['ok.example', 'has space.example']])
        ->assertJsonValidationErrors('add.1');
    $this->postJson('/api/manage/whitelist', ['email' => 'parent@example.test', 'domains' => ['localhost']])
        ->assertJsonValidationErrors('domains.0');
    $this->postJson('/api/manage/whitelist', ['email' => 'parent@example.test', 'remove' => ['https://']])
        ->assertJsonValidationErrors('remove.0');
});
