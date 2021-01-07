<?php

namespace App\Http\Controllers;

use App\EmailActivationUrl;
use App\GlobalWhitelist;
use App\Mail\DeactivationRequestGrantedMail;
use App\Mail\EmailActivation;
use App\User;
use App\UserActivationAttemptResult;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class EmailActivationLinkController extends Controller {
    public function activate(Request $request) {
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

        $userActivateResult = $user->tryActivateUserArray($requestFields);

        $linkModel->delete();

        switch ($userActivateResult) {
            case UserActivationAttemptResult::Success:
                return response('Success!', 200);
            case UserActivationAttemptResult::ActivationLimitExceeded:
                Auth::logout();
                return response('Your account has been activated on more devices than permitted.', 401);
            case UserActivationAttemptResult::AccountDisabled:
                Auth::logout();
                return response('Your account has been disabled.', 401);
            case UserActivationAttemptResult::GroupDisabled:
                Auth::logout();
                return response('The group that your account belongs to has been disabled.', 401);
            case UserActivationAttemptResult::IndentifyingInformationMissing:
                Auth::logout();
                return response('User device identifier and or name not supplied.', 401);
            case UserActivationAttemptResult::UnknownError:
                Auth::logout();
                return response('An unknown error occurred while trying to activate or verify your account activation.', 401);
        }
        return response("", 500);
    }


    public function sendLink(Request $request) {
        $user = User::where("email", $request->input("email"))->first();
        if($user == null) {
            return response('User not found.', 401);
        }

        $expiredTime = Carbon::now()->addHour();
        $url = Url::signedRoute('email_activation_url', [],$expiredTime);
        $parsedUrl = parse_url($url);
        $query = [];
        parse_str($parsedUrl["query"], $query);
        $signature = $query["signature"];

        $emailActivationLink = new EmailActivationUrl();
        $emailActivationLink->expired_at = $expiredTime;
        $emailActivationLink->login_request = $request->all();
        $emailActivationLink->hash = $signature;
        $emailActivationLink->save();

        Mail::to($user->email)
            ->send(new EmailActivation($url));

        return response("", 200);
    }
}
