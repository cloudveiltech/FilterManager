<?php

namespace App\Services;

use App\Jobs\SendDeviceWebhook;
use App\Models\AppUserActivation;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Builds and queues the device webhook cv-manage uses to refresh a customer's devices.
 *
 * The webhook is only a nudge: cv-manage re-reads the customer's activations through
 * `manage/activations?email=`, so the payload just identifies who and which device changed.
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
        self::sendFor($event, $activation->user_id, $activation->identifier, $activation->device_id, $extra, $email);
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

        $email ??= self::emailFor($userId);
        if ($email === null) {
            // Without an owner cv-manage has nothing to refresh; the nightly sync covers it.
            return;
        }

        SendDeviceWebhook::dispatch([
            'event' => $event,
            'email' => $email,
            'identifier' => $identifier,
            'device_id' => $deviceId,
            ...$extra,
            'occurred_at' => Carbon::now()->toIso8601String(),
        ])->afterCommit();
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
