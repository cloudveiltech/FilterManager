<?php

namespace App\Observers;

use App\Models\User;
use App\Services\DeviceWebhook;

/**
 * Notifies cv-manage about customer-level changes that affect all of a customer's devices.
 */
class UserObserver
{
    /**
     * Users are hard-deleted, so capture their devices before the row (and any cascade) is gone.
     */
    public function deleting(User $user): void
    {
        $email = $user->getOriginal('email');
        foreach ($user->activations()->get() as $activation) {
            DeviceWebhook::send(DeviceWebhook::REMOVED, $activation, [], $email);
        }
    }
}
