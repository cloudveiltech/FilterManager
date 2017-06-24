<?php

namespace App\Listeners;

use App\Events\DeactivationRequestGranted;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Mail\DeactivationRequestGrantedMail;
use Illuminate\Support\Facades\Mail;
use Log;

class SendDeactivationRequestGrantedNotification
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
     * @param  DeactivationRequestGranted  $event
     * @return void
     */
    public function handle(DeactivationRequestGranted $event)
    {
        // We access the deactivationRequest via $event->deactivationRequest;
        //Log::info("Logging an object: " . print_r($event, true));
        $user = \App\User::find($event->deactivationRequest->user_id);
        Mail::to($user->email)
          ->send(new DeactivationRequestGrantedMail($event->deactivationRequest, $user));
    }
}
