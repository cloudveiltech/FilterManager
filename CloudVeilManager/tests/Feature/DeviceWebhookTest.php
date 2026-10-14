<?php

use App\Events\DeactivationRequestReceived;
use App\Http\Controllers\ManageActivationController;
use App\Http\Controllers\UserController;
use App\Jobs\SendDeviceWebhook;
use App\Models\AppUserActivation;
use App\Models\DeactivationRequest;
use App\Models\Group;
use App\Models\Helpers\UserActivationAttemptResult;
use App\Models\User;
use Illuminate\Contracts\Queue\Job as QueueJob;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->string('password')->nullable();
        $table->rememberToken();
        $table->boolean('isactive')->default(true);
        $table->unsignedBigInteger('group_id')->nullable();
        $table->unsignedInteger('activations_allowed')->default(5);
        $table->text('config_override')->nullable();
        $table->timestamps();
    });

    Schema::create('app_user_activations', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->unsignedBigInteger('group_id')->nullable();
        $table->string('identifier');
        $table->string('device_id');
        $table->string('platform_name')->nullable();
        $table->string('friendly_name')->nullable();
        $table->string('app_version')->nullable();
        $table->string('ip_address')->nullable();
        $table->boolean('banned')->default(false);
        $table->text('config_override')->nullable();
        $table->timestamp('last_sync_time')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('groups', function (Blueprint $table) {
        $table->id();
        $table->string('name')->nullable();
        $table->boolean('isactive')->default(true);
        $table->timestamps();
    });

    Schema::create('deactivation_requests', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->string('identifier');
        $table->string('device_id');
        $table->boolean('granted')->default(false);
        $table->timestamps();
    });

    config()->set('services.cv_manage.device_webhook_url', 'https://manage.test/webhooks/devices');
    config()->set('services.cv_manage.device_webhook_secret', 'secret-key');

    $this->alice = User::create(['name' => 'Alice', 'email' => 'alice@example.com'])->fresh();
    $this->bob = User::create(['name' => 'Bob', 'email' => 'bob@example.com'])->fresh();
    $this->activation = AppUserActivation::create([
        'user_id' => $this->alice->id,
        'identifier' => 'FP-1',
        'device_id' => 'ALICE-PC',
        'platform_name' => 'WIN',
    ]);

    Queue::fake();
});

afterEach(function () {
    Schema::dropIfExists('deactivation_requests');
    Schema::dropIfExists('app_user_activations');
    Schema::dropIfExists('users');
    Schema::dropIfExists('groups');
});

function sentWebhooks(): array
{
    return Queue::pushed(SendDeviceWebhook::class)
        ->map(fn (SendDeviceWebhook $job) => collect($job->payload)->except('occurred_at')->all())
        ->values()
        ->all();
}

test('a new activation sends added', function () {
    AppUserActivation::create(['user_id' => $this->bob->id, 'identifier' => 'FP-2', 'device_id' => 'BOB-PC']);

    expect(sentWebhooks())->toBe([
        ['event' => 'added', 'email' => 'bob@example.com', 'identifier' => 'FP-2', 'device_id' => 'BOB-PC'],
    ]);
});

test('deleting and restoring an activation sends removed then added', function () {
    $this->activation->delete();
    $this->activation->restore();

    expect(array_column(sentWebhooks(), 'event'))->toBe(['removed', 'added']);
});

test('restoring an activation that was never deleted sends nothing', function () {
    $this->activation->restore();

    expect(sentWebhooks())->toBe([]);
});

test('updates other than a check-in send nothing', function () {
    $this->activation->update([
        'ip_address' => '10.0.0.1',
        'app_version' => '3.0',
        'platform_name' => 'WIN',
    ]);

    expect(sentWebhooks())->toBe([]);
});

test('a check-in sends synced at most once per device per throttle window', function () {
    config()->set('services.cv_manage.device_webhook_sync_minutes', 15);
    $other = AppUserActivation::create(['user_id' => $this->bob->id, 'identifier' => 'FP-2', 'device_id' => 'BOB-PC']);
    Queue::fake();

    $this->activation->update(['last_sync_time' => now()]);
    $this->travel(5)->minutes();
    $this->activation->update(['last_sync_time' => now()]);
    $other->update(['last_sync_time' => now()]);
    $this->travel(11)->minutes();
    $this->activation->update(['last_sync_time' => now()]);

    expect(sentWebhooks())->toBe([
        ['event' => 'synced', 'email' => 'alice@example.com', 'identifier' => 'FP-1', 'device_id' => 'ALICE-PC'],
        ['event' => 'synced', 'email' => 'bob@example.com', 'identifier' => 'FP-2', 'device_id' => 'BOB-PC'],
        ['event' => 'synced', 'email' => 'alice@example.com', 'identifier' => 'FP-1', 'device_id' => 'ALICE-PC'],
    ]);
});

test('moving an activation to another user sends owner_changed', function () {
    $this->activation->update(['user_id' => $this->bob->id]);

    expect(sentWebhooks())->toBe([[
        'event' => 'owner_changed',
        'email' => 'bob@example.com',
        'identifier' => 'FP-1',
        'device_id' => 'ALICE-PC',
        'previous_email' => 'alice@example.com',
    ]]);
});

test('renaming the computer sends renamed with the previous values', function () {
    $this->activation->device_id = 'ALICE-LAPTOP';
    $this->activation->save();

    expect(sentWebhooks())->toBe([[
        'event' => 'renamed',
        'email' => 'alice@example.com',
        'identifier' => 'FP-1',
        'device_id' => 'ALICE-LAPTOP',
        'previous_identifier' => 'FP-1',
        'previous_device_id' => 'ALICE-PC',
    ]]);
});

test('a fingerprint change sends renamed', function () {
    $this->activation->update(['identifier' => 'FP-NEW']);

    expect(sentWebhooks()[0])->toMatchArray([
        'event' => 'renamed',
        'identifier' => 'FP-NEW',
        'previous_identifier' => 'FP-1',
    ]);
});

test('banning and unbanning sends nothing', function () {
    $this->activation->update(['banned' => 1]);
    $this->activation->update(['banned' => 0]);

    expect(sentWebhooks())->toBe([]);
});

test('editing a deleted activation sends nothing', function () {
    $this->activation->delete();
    Queue::fake();

    $this->activation->update(['last_sync_time' => now()]);

    expect(sentWebhooks())->toBe([]);
});

test('customer changes other than delete send nothing', function () {
    $this->alice->update(['name' => 'Alice Smith', 'isactive' => 0]);

    expect(sentWebhooks())->toBe([]);
});

test('deleting a customer sends removed for each of their devices', function () {
    $this->alice->delete();

    expect(sentWebhooks())->toBe([
        ['event' => 'removed', 'email' => 'alice@example.com', 'identifier' => 'FP-1', 'device_id' => 'ALICE-PC'],
    ]);
});

test('a device asking to deactivate sends deactivation_changed once', function () {
    Event::fake([DeactivationRequestReceived::class]);
    $this->actingAs($this->alice);
    $request = Request::create('/', 'POST', ['identifier' => 'FP-1', 'device_id' => 'ALICE-PC']);

    app(UserController::class)->getCanUserDeactivate($request);
    app(UserController::class)->getCanUserDeactivate($request);

    expect(sentWebhooks())->toBe([
        ['event' => 'deactivation_changed', 'email' => 'alice@example.com', 'identifier' => 'FP-1', 'device_id' => 'ALICE-PC'],
    ]);
});

test('granting and deleting a deactivation request sends deactivation_changed', function () {
    $deactivation = DeactivationRequest::create(['user_id' => $this->alice->id, 'identifier' => 'FP-1', 'device_id' => 'ALICE-PC']);
    Queue::fake();

    $deactivation->update(['granted' => 1]);
    $deactivation->update(['granted' => 1]);
    $deactivation->delete();

    expect(array_column(sentWebhooks(), 'event'))->toBe(['deactivation_changed', 'deactivation_changed']);
});

test('a granted device-side deactivation sends deactivation_changed and removed', function () {
    DeactivationRequest::create([
        'user_id' => $this->alice->id,
        'identifier' => 'FP-1',
        'device_id' => 'ALICE-PC',
        'granted' => 1,
    ]);
    Queue::fake();
    $this->actingAs($this->alice);

    $response = app(UserController::class)->getCanUserDeactivate(
        Request::create('/', 'POST', ['identifier' => 'FP-1', 'device_id' => 'ALICE-PC'])
    );

    expect($response->status())->toBe(204)
        ->and(AppUserActivation::count())->toBe(0)
        ->and(sentWebhooks())->toBe([
            ['event' => 'deactivation_changed', 'email' => 'alice@example.com', 'identifier' => 'FP-1', 'device_id' => 'ALICE-PC'],
            ['event' => 'removed', 'email' => 'alice@example.com', 'identifier' => 'FP-1', 'device_id' => 'ALICE-PC'],
        ]);
});

test('logging in as another user on the same machine retires the old activation', function () {
    $result = $this->bob->tryActivateUserArray(['identifier' => 'FP-1', 'device_id' => 'ALICE-PC']);

    expect($result)->toBe(UserActivationAttemptResult::Success)
        ->and($this->activation->fresh()->trashed())->toBeTrue()
        ->and(sentWebhooks())->toBe([
            ['event' => 'added', 'email' => 'bob@example.com', 'identifier' => 'FP-1', 'device_id' => 'ALICE-PC'],
            ['event' => 'removed', 'email' => 'alice@example.com', 'identifier' => 'FP-1', 'device_id' => 'ALICE-PC'],
        ]);
});

test('a login rejected for the license limit keeps the old activation', function () {
    $this->bob->update(['activations_allowed' => 0]);
    config()->set('app.license_overage_allowed', 0);

    $result = $this->bob->tryActivateUserArray(['identifier' => 'FP-1', 'device_id' => 'ALICE-PC']);

    expect($result)->toBe(UserActivationAttemptResult::ActivationLimitExceeded)
        ->and($this->activation->fresh()->trashed())->toBeFalse();
});

test('logging in again on your own device sends nothing', function () {
    $result = $this->alice->tryActivateUserArray(['identifier' => 'FP-1', 'device_id' => 'ALICE-PC']);

    expect($result)->toBe(UserActivationAttemptResult::Success)
        ->and(sentWebhooks())->toBe([]);
});

test('nothing is queued when the webhook url is blank', function () {
    config()->set('services.cv_manage.device_webhook_url', '');

    $this->activation->delete();

    expect(sentWebhooks())->toBe([]);
});

test('the job posts the payload with the api key', function () {
    Http::fake();

    (new SendDeviceWebhook($this->activation->id, 'added', [], 'alice@example.com'))->handle();

    Http::assertSent(fn ($request) => $request->url() === 'https://manage.test/webhooks/devices'
        && $request->hasHeader('X-Api-Key', 'secret-key')
        && $request['event'] === 'added'
        && $request['email'] === 'alice@example.com');
});

test('the payload carries the same device row the per-email api returns', function () {
    $group = Group::create(['name' => 'Family']);
    $this->activation->update(['group_id' => $group->id]);
    DeactivationRequest::create(['user_id' => $this->alice->id, 'identifier' => 'FP-1', 'device_id' => 'ALICE-PC']);
    Http::fake();

    (new SendDeviceWebhook($this->activation->id, 'added', [], 'alice@example.com'))->handle();

    $row = json_decode(json_encode(app(ManageActivationController::class)->index(Request::create('/', 'GET', ['email' => 'alice@example.com']))->getData(true)[0]), true);
    Http::assertSent(function ($request) use ($row) {
        return $request['device'] === $row
            && $request['device']['group']['name'] === 'Family'
            && count($request['device']['deactivation_request']) === 1;
    });
});

test('the device is the soft-deleted row for removed and null when the row is gone', function () {
    Http::fake();
    $id = $this->activation->id;
    $this->activation->delete();

    (new SendDeviceWebhook($id, 'removed', [], 'alice@example.com'))->handle();
    (new SendDeviceWebhook(null, 'removed', [], 'alice@example.com'))->handle();

    $devices = [];
    Http::assertSentCount(2);
    Http::assertSent(function ($request) use (&$devices) {
        $devices[] = $request['device'];

        return true;
    });
    expect($devices[0]['deleted_at'])->not->toBeNull()
        ->and($devices[1])->toBeNull();
});

test('occurred_at is stamped when the job runs, not when it is queued', function () {
    Http::fake();
    $job = new SendDeviceWebhook($this->activation->id, 'added', [], 'alice@example.com');

    $this->travelTo(now()->addMinutes(30));
    $job->handle();

    Http::assertSent(fn ($request) => $request['occurred_at'] === now()->toIso8601String());
});

test('the job throws so it is retried when cv-manage is down', function () {
    Http::fake(['*' => Http::response('', 503)]);

    (new SendDeviceWebhook($this->activation->id, 'added', [], 'alice@example.com'))->handle();
})->throws(RequestException::class);

test('the job throws so it is retried when rate limited', function () {
    Http::fake(['*' => Http::response('', 429)]);

    (new SendDeviceWebhook($this->activation->id, 'added', [], 'alice@example.com'))->handle();
})->throws(RequestException::class);

test('a rejected request is failed instead of retried', function () {
    Http::fake(['*' => Http::response('', 422)]);
    $queueJob = Mockery::mock(QueueJob::class);
    $queueJob->shouldReceive('fail')->once();
    $job = new SendDeviceWebhook($this->activation->id, 'added', [], 'alice@example.com');
    $job->setJob($queueJob);

    $job->handle();

    Http::assertSentCount(1);
});

test('the listing rows carry deactivation_request and the owner email without a query per row', function () {
    DeactivationRequest::create(['user_id' => $this->alice->id, 'identifier' => 'FP-1', 'device_id' => 'ALICE-PC']);
    $listing = fn () => app(ManageActivationController::class)->index(Request::create('/', 'GET'));
    $queries = function () use ($listing) {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $rows = $listing()->getCollection();
        DB::disableQueryLog();

        return [count(DB::getQueryLog()), $rows];
    };

    [$before, $rows] = $queries();
    foreach (range(1, 5) as $i) {
        AppUserActivation::create(['user_id' => User::create(['name' => "U{$i}", 'email' => "u{$i}@example.com"])->id, 'identifier' => "FP-X{$i}", 'device_id' => "PC-{$i}"]);
    }
    [$after, $more] = $queries();

    $row = json_decode($rows->first()->toJson(), true);
    expect($row)->toHaveKey('deactivation_request')
        ->and($row['deactivation_request'])->toHaveCount(1)
        ->and($row['user']['email'])->toBe('alice@example.com')
        ->and($row['user'])->not->toHaveKey('activations_used')
        ->and($more)->toHaveCount(6)
        ->and($after)->toBe($before);
});
