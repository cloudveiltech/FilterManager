<?php

namespace App\Observers;

use App\Models\DeactivationRequest;
use App\Services\DeviceWebhook;

/**
 * Notifies cv-manage when a device's deactivation request is opened, granted or closed,
 * so support sees it before the nightly sync.
 */
class DeactivationRequestObserver
{
    public function created(DeactivationRequest $request): void
    {
        $this->send($request);
    }

    public function updated(DeactivationRequest $request): void
    {
        if ($request->wasChanged('granted')) {
            $this->send($request);
        }
    }

    public function deleted(DeactivationRequest $request): void
    {
        $this->send($request);
    }

    private function send(DeactivationRequest $request): void
    {
        DeviceWebhook::sendFor(
            DeviceWebhook::DEACTIVATION_CHANGED,
            $request->user_id,
            $request->identifier,
            $request->device_id,
        );
    }
}
