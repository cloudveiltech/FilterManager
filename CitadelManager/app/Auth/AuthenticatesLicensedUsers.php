<?php

/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace App\Auth;

use Illuminate\Http\Request;
use App\UserActivationAttemptResult;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Validator;

/**
 * Description of AthenticatesLicensedUsers
 *
 */
trait AuthenticatesLicensedUsers {

    use AuthenticatesUsers {
        AuthenticatesUsers::authenticated as authenticated;
        AuthenticatesUsers::logout as logout;
    }

    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        $this->guard()->logout();

        $request->session()->flush();

        $request->session()->regenerate();

        return redirect(config('app.url'));
    }
    
    /**
     * The user has been authenticated. Now we want to do some checks
     * and enforce some extra auth stuff for our app. If these checks
     * fail, we'll boot the user by killing the session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user) {

        // We need to enforce license activations, even for admin users,
        // but only if data identifying a device is set. If such data
        // is not present, and the authenticated user is a plain old
        // user rather than an admin, we need to boot them out by
        // killing the session and letting them know that they are not
        // permitted to authenticate over the web portal.
        $validator = Validator::make($request->all(), [
                    'identifier' => 'required',
                    'device_id' => 'required'
        ]);

        if (!$validator->fails()) {
            handleAppUserValidation($request, $user);
        } else {
            if ($user->hasRole('user')) {
                $this->logout($request);
                return response('Regular users are not permitted to authenticate via the web portal.', 401);
            }
        }
    }

    private function handleAppUserValidation(Request $request, App\User $user) {
        $userActivateResult = $user->tryActivateUser($request);

        switch ($userActivateResult) {
            case UserActivationAttemptResult::Success: {
                    // It's assumed that app users will only be calling the parent trait's
                    // login info from a POST command from the app, so they are not to be
                    // redirected anywhere. A simple 204 no content response will indicate
                    // success.
                    return response('', 204);
                }
                break;

            case UserActivationAttemptResult::ActivationLimitExceeded: {
                    $this->logout($request);
                    return response('Your account has been activated on more devices than permitted.', 401);
                }
                break;

            case UserActivationAttemptResult::AccountDisabled: {
                    $this->logout($request);
                    return response('Your account has been disabled.', 401);
                }
                break;

            case UserActivationAttemptResult::GroupDisabled: {
                    $this->logout($request);
                    return response('The group that your account belongs to has been disabled.', 401);
                }
                break;

            case UserActivationAttemptResult::IndentifyingInformationMissing: {
                    $this->logout($request);
                    return response('User device identifier and or name not supplied.', 401);
                }
                break;

            case UserActivationAttemptResult::UnknownError: {
                    $this->logout($request);
                    return response('An unknown error occurred while trying to activate or verify your account activation.', 401);
                }
                break;
        }
    }

}
