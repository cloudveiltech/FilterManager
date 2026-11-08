<?php

namespace App\Observers;

use App\Models\AppUserActivation;
use App\Services\DeviceWebhook;

/**
 * Notifies cv-manage when an activation is added, removed, moved, renamed or checks in.
 * Check-ins are throttled per device, since devices sync constantly.
 */
class AppUserActivationObserver
{
    public function created(AppUserActivation $activation): void
    {
        DeviceWebhook::send(DeviceWebhook::ADDED, $activation);
    }

    public function restored(AppUserActivation $activation): void
    {
        // restore() is called on every login, even for activations that were never deleted.
        if (($activation->getPrevious()['deleted_at'] ?? null) === null) {
            return;
        }

        DeviceWebhook::send(DeviceWebhook::ADDED, $activation);
    }

    public function deleted(AppUserActivation $activation): void
    {
        DeviceWebhook::send(DeviceWebhook::REMOVED, $activation);
    }

    public function updated(AppUserActivation $activation): void
    {
        // Admins can edit deleted activations; cv-manage no longer lists those.
        if ($activation->trashed()) {
            return;
        }

        $previous = $activation->getPrevious();

        if ($activation->wasChanged('user_id')) {
            DeviceWebhook::send(DeviceWebhook::OWNER_CHANGED, $activation, [
                'previous_email' => DeviceWebhook::emailFor($previous['user_id'] ?? null),
            ]);
        }

        if ($activation->wasChanged(['identifier', 'device_id'])) {
            DeviceWebhook::send(DeviceWebhook::RENAMED, $activation, [
                'previous_identifier' => $previous['identifier'] ?? $activation->identifier,
                'previous_device_id' => $previous['device_id'] ?? $activation->device_id,
            ]);
        }

        if ($activation->wasChanged('last_sync_time')) {
            DeviceWebhook::sendSynced($activation);
        }
    }
}
