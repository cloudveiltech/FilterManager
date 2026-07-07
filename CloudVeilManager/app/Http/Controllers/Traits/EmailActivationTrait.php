<?php

namespace App\Http\Controllers\Traits;

use App\Models\EmailActivationUrl;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

trait EmailActivationTrait
{
    public function sendLinkToEmail(Request $request, $emailClass, $urlName)
    {
        $user = User::where("email", $request->input("email"))->first();
        if ($user == null) {
            return response('User not found.', 401);
        }

        $expiredTime = Carbon::now()->addHour();
        $url = Url::signedRoute($urlName, [], $expiredTime);
        $parsedUrl = parse_url($url);
        $query = [];
        parse_str($parsedUrl["query"], $query);
        $signature = $query["signature"];

        $emailActivationLink = new EmailActivationUrl();
        $emailActivationLink->expired_at = $expiredTime;
        $emailActivationLink->login_request = $request->all() + ["ip" => $request->ip(), 'url' => $url];
        $emailActivationLink->hash = $signature;
        $emailActivationLink->save();
        Mail::to($user->email)
            ->send(new $emailClass($url));

        return response(["type" => "link"], 200);
    }

    public function activateLink(Request $request, $activationClassModelClass)
    {
        EmailActivationUrl::removeExpiredUrls();
        if (!$request->hasValidSignature()) {
            abort(401);
        }

        $linkModel = EmailActivationUrl::findOrFail($request->get("signature"));
        if ($linkModel->isExpired()) {
            abort(401);
        }

        $requestFields = $linkModel->login_request;
        $user = User::where("email", $requestFields["email"])->firstOrFail();

        $activationInfo = [
            "device_token" => $requestFields['device_token'],
            "device_name" => $requestFields['device_name'],
            "ip_address" => $requestFields['ip'],
            "app_version" => $requestFields['app_version'],
            'user_id' => $user->id,
            'os' => $requestFields['os']
        ];

        $activation = $activationClassModelClass::firstOrCreate($activationInfo);

        $linkModel->delete();

        return response("Activation successful! You may continue using the CloudVeil app.", 200);
    }
}
