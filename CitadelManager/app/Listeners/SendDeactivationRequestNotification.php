<?php

namespace App\Listeners;

use App\Events\DeactivationRequestReceived;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Mail\DeactivationRequestReceivedMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Log;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;

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
        $user = \App\User::find($event->deactivationRequest->user_id);
        Mail::to($user->email)
          ->send(new DeactivationRequestReceivedMail($event->deactivationRequest, $user));
        //Log::debug('Sent Deactivation Request to ' . $user->email);
        $data = [];
        $data['user'] = $user;
        $data['deactivationRequest'] = $event->deactivationRequest;
        $contents = view('emails.deactivation_request_received', $data);
        
        $client = new Client(); //GuzzleHttp\Client
        $result = $client->post('https://manage.cloudveil.org/api/v1/accountability_partners/notify', [
            'form_params' => [
                'email' => $user->email,
                'message' => $contents->render(),
                'subject' => 'CloudVeil For Windows Deactivation Request Received',
                'alert' => ''
            ]
        ]);
        
    }
}
