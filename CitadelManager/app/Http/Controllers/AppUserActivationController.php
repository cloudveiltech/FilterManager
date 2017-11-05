<?php

/*
 * Copyright � 2017 Darren Wiebe
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace App\Http\Controllers;

use Validator;
use App\User;
use App\Group;
use App\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\AppUserActivation;
use Laravel\Passport\Passport;
use Log;
use Carbon\Carbon;

class AppUserActivationController extends Controller {

    public function __construct() {
    
    }

    /**
     * Returns the list of activations.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request) {
      //$thisUser = \Auth::user();
      if ($request->has('email')) {
          $user = User::where('email', $request->input('email'))->first();
          if ($user && $user->activations()) {
              return $user->activations()->get();
          } else {
              return response()->json([]);
          }

          //$activations = AppUserActivation::where
      } else if ($request->has('user_id')) {
          $user = User::find($request->input('user_id'));
          if ($user && $user->activations()) {
              return $user->activations()->get();
          } else {
              return response()->json([]);
          } 
      } else {
          return AppUserActivation::get();
      }
    }

}
