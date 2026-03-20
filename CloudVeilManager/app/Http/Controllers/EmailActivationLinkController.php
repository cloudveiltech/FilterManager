<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\EmailActivationTrait;
use App\Mail\EmailTwoFactorActivation;
use App\Models\EmailActivationUrl;
use App\Models\TwoFactorCode;
use App\Models\User;
use App\Models\Helpers\UserActivationAttemptResult;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class EmailActivationLinkController extends Controller {
    use EmailActivationTrait;

    function activateOverEmail(Request $request)
    {
        $user = User::where("email", $request->input("email"))->first();
        if ($user == null) {
            return response("User Not Found.", 401);
        }
        return $this->sendLinkToEmail($request, "App\Mail\EmailActivation", "email_activation_url");
    }

    /**
     * @throws \Exception
     */
    function send2FACode(Request $request)
    {
        $user = User::where("email", $request->input("email"))->first();
        if ($user == null) {
            return response("User Not Found.", 401);
        }

        TwoFactorCode::removeExpiredCodes();
        TwoFactorCode::dropCodesForUserId($user->id);

        $expiredTime = Carbon::now()->addHours(TwoFactorCode::EXPIRATION_INTERVAL_HR);
        $twoFactorCode = new TwoFactorCode();
        $twoFactorCode->expired_at = $expiredTime;
        $twoFactorCode->code = random_int(100000, 999999);
        $twoFactorCode->user_id = $user->id;
        $twoFactorCode->save();

        Mail::to($user->email)
            ->send(new EmailTwoFactorActivation($twoFactorCode->code));

        return response(["type" => "2fa"], 200);
    }

    function activate2FA(Request $request)
    {
        if(!$request->has(["email", "identifier", "device_id", "code"])) {
            return response('Some required fields missing.', 401);
        }

        $user = User::where("email", $request->input("email"))->first();
        if ($user == null) {
            return response('User not found.', 401);
        }
        if (!$user->is2FAAuthEnabled()) {
            return response('Two factor authentication not allowed. Use other methods instead.', 403);
        }
        TwoFactorCode::removeExpiredCodes();

        $code = $request->input("code");

        $twoFactorCode = TwoFactorCode::where("user_id", $user->id)->where("code", $code)->first();
        if (!$twoFactorCode) {
            return response("Invalid 2FA Code.", 401);
        }
        $twoFactorCode->delete();

        $this->activateUser($request->all());

        return response(["type" => "2fa"], 200);
    }

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

        $result = $this->activateUser($requestFields);
        $linkModel->delete();
        return $result;
    }

    private function activateUser($requestFields) {
        $user = User::where("email", $requestFields["email"])->firstOrFail();

        $userActivateResult = $user->tryActivateUserArray($requestFields);

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
}
