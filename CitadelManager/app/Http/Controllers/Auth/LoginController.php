<?php

/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Auth\AuthenticatesLicensedUsers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesLicensedUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */

    protected $innerRedirectTo = null;

    protected function redirectTo() {
        $user = Auth::user();

        if($this->innerRedirectTo !== null) {
            return $this->innerRedirectTo;
        }

        if($user->hasRole('user')) {
            return '/user';
        } else {
            return '/admin';
        }
    }

    public function login(Request $request) {
        $redirect = $request->input('redirect');

        if($redirect != null) {
            $this->innerRedirectTo = $redirect;
        }

        return parent::login($request);
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        
    }
}
