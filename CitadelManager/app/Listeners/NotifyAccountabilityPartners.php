<?php

namespace App\Listeners;

use App\Events\AccountabilityPartnerEvent;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

class NotifyAccountabilityPartners implements ShouldQueue
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
     * @param  AccountabilityPartnerEvent  $event
     * @return void
     */
    public function handle(AccountabilityPartnerEvent $event)
    {
        Log::error(json_encode($event));
        if (config('app.accountability.enabled')) {
            Log::error('Sending Accountability Report.');
        // If we hit this  we need to send the data off to this endpoint.
	$body = [
	    'user_email' => $event->user->email,
            'data_type' => $event->data_type,
            'source_name' => $event->source_name,
            'url' => $event->url,
            'message' => $event->message,
            'importance_level' => $event->severity,
	    'certainty_level' => $event->certainty,
	];

	$client = new Client([
            'base_uri' => config('app.accountability.url'),
            'headers' => ['Content-Type' => 'application/json'],
            'verify' => false
        ]);

        $response = $client->post('api/v1/data/general', [
            'headers' => [
                'Accept'     => 'application/json',
                'Content-Type'     => 'application/json',
		'Authorization' => 'Bearer ' . $this->getAccessToken()
            ],
            RequestOptions::JSON => $body
        ]);
        } else {
            Log::error('Accountability Reporting Disabled.');
        }
    }


    public static function getAccessToken()
    {
        $token = Cache::get("acc_token");
        if ($token != null) {
            return $token;
        }

	$client = new Client([
            'base_uri' => config('app.accountability.url'),
            'headers' => ['Content-Type' => 'application/json'],
            'verify' => false
        ]);

        $body = [
            'email' => config('app.accountability.email'),
            'passcode' => config('app.accountability.passcode'),
        ];

        // Send Message
        $res = $client->post('api/v1/user/token/write', [
            RequestOptions::JSON => $body
        ]);

        if ($res != null) {
            $data = json_decode($res->getBody()->getContents());
            if ($data) {
                $token = $data->token;
                Cache::forever("acc_token", $token);
                return $token;
            }
        }
    }
}
