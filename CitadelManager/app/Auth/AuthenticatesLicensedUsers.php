<?php

/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace App\Auth;

use Illuminate\Http\Request;
use App\User;
use App\UserActivationAttemptResult;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Validator;
use Illuminate\Support\Facades\Auth;

/**
 * Description of AthenticatesLicensedUsers
 *
 */
trait AuthenticatesLicensedUsers {

    use AuthenticatesUsers {
        AuthenticatesUsers::authenticated as authenticated;
        AuthenticatesUsers::logout as logout;
        AuthenticatesUsers::login as login;
        AuthenticatesUsers::showLoginForm as showLoginForm;
    }

    /**
    * Show the application login form.
    *
    * @return \Illuminate\Http\Response
    */
    public function showLoginForm()
    {
        if(Auth::check())
        {
            return redirect()->intended($this->redirectPath());
        }
        
        return view('auth.login');
    }
    
    /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        if (Auth::check()) {
            
            $validator = Validator::make($request->all(), [
                    'identifier' => 'required',
                    'device_id' => 'required'
            ]);

            if (!$validator->fails()) {
                $thisUser = Auth::user();
                return $this->handleAppUserValidation($request, $thisUser);
            }
            
            return redirect()->intended($this->redirectPath());
        }
        
        $this->validateLogin($request);

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        if ($this->attemptLogin($request)) {
            return $this->sendLoginResponse($request);
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
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
            $this->handleAppUserValidation($request, $user);
        } else {
            if ($user->hasRole('user')) {
                $this->logout($request);
                return response('Regular users are not permitted to authenticate via the web portal.', 401);
            }
        }
    }

    private function handleAppUserValidation(Request $request, User $user) {
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
