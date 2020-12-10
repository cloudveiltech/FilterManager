<?php

namespace App\Listeners;

use App\Events\AccountabilityPartnerEvent;
use App\Events\DeactivationRequestReceived;
use App\Mail\DeactivationRequestReceivedMail;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;

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
                'alert' => '',
            ],
        ]);
        event(new AccountabilityPartnerEvent(
            $user,
            'CV4W_DEACTIVATION_REQUEST',
            'CV4W_WEBHOOK',
            '',
            $contents->render(),
            2,
            10)
        );


    }
}
