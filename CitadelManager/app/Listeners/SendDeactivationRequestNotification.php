<?php

namespace App\Listeners;

use App\Events\DeactivationRequestReceived;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Mail\DeactivationRequestReceivedMail;
use Illuminate\Support\Facades\Mail;

class SendDeactivationRequestNotification
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  DeactivationRequestReceived  $event
     * @return void
     */
    public function handle(DeactivationRequestReceived $event)
    {
        // We access the deactivationRequest via $event->deactivationRequest;
        Mail::to('darren@aleph-com.net')->send(new DeactivationRequestReceivedMail($event));
    }
}
