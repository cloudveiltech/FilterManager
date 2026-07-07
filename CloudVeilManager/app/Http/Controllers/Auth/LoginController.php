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
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class LoginControllerBase extends Controller {
    // LoginControllerBase -> LoginController allows us to override this trait.
    use AuthenticatesLicensedUsers;
}

class LoginController extends LoginControllerBase
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
        } else if($user->hasRole('business-owner')) {
            return '/user';
        } else {
            return '/admin';
        }
    }

    public function showLoginForm(Request $request) {
        if(config("auth.disable_email_auth") == 1) {
            $ssoProvider = config("auth.default_sso_provider");

            if($ssoProvider == null) {
                $ssoProvider = "cloudveil";
            }

            return $this->loginWithProvider($request, $ssoProvider);
        }

        return parent::showLoginForm($request);
    }

    public function login(Request $request) {
        $redirect = $request->input('redirect');

        if($redirect != null) {
            $this->innerRedirectTo = $redirect;
        }

        return parent::login($request);
    }

    public function loginWithProvider(Request $request, $provider) {
        return Socialite::with($provider)->redirect();
    }

    public function handleProviderCallback(Request $request, $provider) {
        $user = Socialite::with($provider)->user();

        $authUser = $this->findOrCreateUser($user, $provider);
        Auth::login($authUser, true);

        $redirect = $request->input('redirect');

        if($redirect != null) {
            $this->innerRedirectTo = $redirect;
        }

        return redirect($this->redirectTo());
    }

    private function findOrCreateUser($user, $provider) {
        $authUser = User::where('provider', $provider)->where('provider_id', $user->id)->first();

        if ($authUser) {
            $authUser->email = $user->email;
            $authUser->save();
            return $authUser;
        }

        $authUser = User::where('email', $user->email)->first();

        if($authUser) {
            $authUser->provider = $provider;
            $authUser->provider_id = $user->id;
            $authUser->save();
            return $authUser;
        }

        $role = Role::where('name', 'user')->first();

        $user = User::create([
            'name'     => $user->name,
            'email'    => $user->email,
            'password' => '',
            'provider' => $provider,
            'provider_id' => $user->id
        ]);

        $user->attachRole($role);

        return $user;
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
