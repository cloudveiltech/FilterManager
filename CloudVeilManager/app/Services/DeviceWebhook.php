<?php

namespace App\Services;

use App\Jobs\SendDeviceWebhook;
use App\Models\AppUserActivation;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Builds and queues the device webhook cv-manage uses to refresh a customer's devices.
 *
 * The job attaches the activation row (as `manage/activations?email=` returns it) when it is sent,
 * so cv-manage can update its copy without a round trip.
 */
class DeviceWebhook
{
    public const ADDED = 'added';

    public const REMOVED = 'removed';

    public const OWNER_CHANGED = 'owner_changed';

    public const RENAMED = 'renamed';

    public const SYNCED = 'synced';

    public const DEACTIVATION_CHANGED = 'deactivation_changed';

    /**
     * Queue a webhook for the activation, sent once any open transaction commits.
     *
     * @param  string|null  $email  Owner email; looked up from the activation's user when null.
     */
    public static function send(string $event, AppUserActivation $activation, array $extra = [], ?string $email = null): void
    {
        self::dispatch($event, $activation->getKey(), $activation->user_id, $activation->identifier, $activation->device_id, $extra, $email);
    }

    /**
     * Queue a webhook for a device identified by its owner and identifiers.
     *
     * @param  string|null  $email  Owner email; looked up from $userId when null.
     */
    public static function sendFor(string $event, $userId, ?string $identifier, ?string $deviceId, array $extra = [], ?string $email = null): void
    {
        if (blank(config('services.cv_manage.device_webhook_url'))) {
            return;
        }

        $activationId = AppUserActivation::withTrashed()
            ->where('user_id', $userId)
            ->where('identifier', $identifier)
            ->where('device_id', $deviceId)
            ->value('id');

        self::dispatch($event, $activationId, $userId, $identifier, $deviceId, $extra, $email);
    }

    private static function dispatch(string $event, $activationId, $userId, ?string $identifier, ?string $deviceId, array $extra, ?string $email): void
    {
        if (blank(config('services.cv_manage.device_webhook_url'))) {
            return;
        }

        $email ??= self::emailFor($userId);
        if ($email === null) {
            // Without an owner cv-manage has nothing to refresh; the nightly sync covers it.
            return;
        }

        SendDeviceWebhook::dispatch($activationId, $event, [
            'identifier' => $identifier,
            'device_id' => $deviceId,
            ...$extra,
        ], $email)->afterCommit();
    }

    /**
     * Send a synced webhook at most once per device per throttle window, since devices check in constantly.
     */
    public static function sendSynced(AppUserActivation $activation): void
    {
        if (blank(config('services.cv_manage.device_webhook_url'))) {
            return;
        }

        $minutes = (int) config('services.cv_manage.device_webhook_sync_minutes');
        if (! Cache::add('device-webhook:synced:'.$activation->getKey(), true, now()->addMinutes($minutes))) {
            return;
        }

        self::send(self::SYNCED, $activation);
    }

    public static function emailFor($userId): ?string
    {
        return $userId === null ? null : User::whereKey($userId)->value('email');
    }
}
