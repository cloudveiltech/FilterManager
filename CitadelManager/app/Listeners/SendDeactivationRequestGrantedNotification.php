<?php

namespace App\Listeners;

use App\Events\DeactivationRequestGranted;
use App\Mail\DeactivationRequestGrantedMail;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Mail;

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
        $data = [];
        $data['user'] = $user;
        $data['deactivationRequest'] = $event->deactivationRequest;
        $contents = view('emails.deactivation_request_granted', $data);
        //Log::debug($contents);
        $client = new Client(); //GuzzleHttp\Client
        $result = $client->post('https://manage.cloudveil.org/api/v1/accountability_partners/notify', [
            'form_params' => [
                'email' => $user->email,
                'message' => $contents->render(),
                'subject' => 'CloudVeil For Windows Deactivation Request Granted',
                'alert' => '',
            ],
        ]);
    }
}
