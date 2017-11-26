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
use App\Events\ActivationBypassDenied;
use App\Events\ActivationBypassGranted;
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
    public function index(Request $request, $user_id = null) {
      if ($request->has('email')) {
          $user = User::where('email', $request->input('email'))->first();
          if ($user && $user->activations()) {
              return $user->activations()->get();
          } else {
              return response()->json([]);
          }
      } else if ($request->has('user_id') || $user_id != null) {
          $user_id = ($user_id != null ? $user_id : $request->has('user_id'));
          $user = User::find($user_id);
          if ($user && $user->activations()) {
              return $user->activations()->get();
          } else {
              return response()->json([]);
          } 
      } else {
          return AppUserActivation::with('user')->get();
      }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        
        $activation = AppUserActivation::where('id', $id)->first();
        if (!is_null($activation)) {
            $activation->delete();
        }

        return response('', 204);
    }

    /**
     * Block the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function block($id) {
        Log::debug($id);
        $user = \Auth::user();
        $userTokens = $user->tokens;
        //Log::debug(count($userTokens));
        if (count($userTokens) > 0) {
            foreach($userTokens as $token) {
                $token->revoke();   
            // Log::debug($token);
            }  
        }
        $activation = AppUserActivation::where('id', $id)->first();
        Log::debug($activation);
        if (!is_null($activation)) {
            $activation->delete();
        }
        
        return response('', 204);
    }

    public function update(Request $request, $id) {
        
        // The javascript side/admin UI will not send
        // password or password_verify unless they are
        // intentionally trying to change a user's password.
    
        $input = $request->only(['bypass_quantity', 'bypass_period']);
        AppUserActivation::where('id', $id)->update($input);

        return response('', 204);
    }
    public function show($id)
    {
        return AppUserActivation::where('id', $id)->get();
    }
    /**
     * Block the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function status($identify) {
        // Get Specific Activation with $identify
        $activations = AppUserActivation::where('identifier', $identify)->get();
        if (count($activations) == 0) {
            $arr = array(
                "allowed" => false,
                "message" => "Request denied. Unknown identify is used." 
            );
            return response()->json($arr);    
        }
        $activation = $activations->first();


        $bypass_permitted = 0;
        if(is_null($activation->bypass_quantity)) {
            $user_id = $activation->user_id;
            $user = User::where('id', $user_id)->first();

            if($user->group_id == -1) {
                // group is not assigned to user.
                $arr_output = array(
                    "allowed" => false,
                    "message" => "Request denied. User should be set his own group." 
                );
                return response()->json($arr_output);    
            } else {
                // group is assigned
                $group = $user->group()->first();
                $app_cfg_str = $group->app_cfg;
                $app_cfg = json_decode($app_cfg_str);
    
                if (is_null($app_cfg->BypassesPermitted)) {
                    $bypass_permitted = 0;
                } else {
                    $bypass_permitted = $app_cfg->BypassesPermitted;
                }
            }

        } else {
            $bypass_permitted = $activation->bypass_quantity;
        }

        // Check current status
        $bypass_used = $activation->bypass_used;
        if ( $bypass_permitted > $bypass_used) {
            // status : granted
            $arr_output = array(
                "allowed" => true,
                "message" => "Request granted. Used ". $bypass_used ." out of ". $bypass_permitted ."." 
            );

            // Trigger of bypass_granted
            try {
                event(new ActivationBypassGranted($activation));
            } catch(\Exception $e){
                Log::error($e);
            }
            
            return response()->json($arr_output);
        } else {
            // status: denied
            $arr_output = array(
                "allowed" => false,
                "message" => "Request denied. You have already used  ". $bypass_used ." out of ". $bypass_permitted ."." 
            );
            // Trigger of bypass_denied
            try {
                event(new ActivationBypassDenied($activation));
            } catch(\Exception $e){
                Log::error($e);
            }
            
            return response()->json($arr_output);
        }
    }
}
