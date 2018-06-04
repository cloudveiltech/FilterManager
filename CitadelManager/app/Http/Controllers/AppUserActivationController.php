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
                return $user->activations()->with('deactivation_request')->get();
            } else {
                return response()->json([]);
            }
        } else if ($request->has('user_id') || $user_id != null) {
            $user_id = ($user_id != null ? $user_id : $request->has('user_id'));
            $user = User::find($user_id);
            if ($user && $user->activations()) {
            $activations = $user->activations()->with('deactivation_request')->get();
                return $activations;
            } else {
                return response()->json([]);
            } 
        }

        $draw = $request->input('draw');
        $start = $request->input('start');
        $length = $request->input('length');
        $search = $request->input('search')['value'];

        $order = $request->input('order')[0]['column'];
        $order_name = $request->input('columns')[intval($order)]['data'];
        $order_str = $request->input('order')[0]['dir'];

        $recordsTotal = AppUserActivation::count();
        $query = AppUserActivation::leftJoin("users", "users.id","=", "app_user_activations.user_id")
            ->select('app_user_activations.*','users.name')
            ->when($search, function($query) use($search) {
                return $query->where('users.name', 'like',"%$search%")
                    ->orWhere('app_user_activations.device_id', 'like', "%$search%");
            }, function ($query) use ($order_str, $order_name) {
                return $query->orderBy($order_name, $order_str);
            });

        $recordsFilterTotal = $query->count();
            
        $rows = $query->offset($start)
            ->limit($length)
            ->get();
        
        return response()->json([
            "draw" => intval($draw),
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsFilterTotal,
            "data" => $rows
        ]);
      
    }
    public function updateReport(Request $request) {
        $id = $request->input('id');
        $value = intval($request->input('value'));   //0 or 1

        $id_arr = explode("_", $id);
        if($id_arr[0] != "useractivation") {
            return response()->json([
                "success" => false
            ]);
        }
        $activation_id = intval($id_arr[2]);
        AppUserActivation::where('id', $activation_id)->update(['report_level'=>$value]);
       
        return response()->json([
            "success" => true
        ]);
    }

    public function updateAlert(Request $request) {
        $id = $request->input('id');
        $value = intval($request->input('value'));   //0 or 1
        AppUserActivation::where('id', $id)->update(['alert_partner'=>$value]);
        return response()->json([
            "success" => true
        ]);
    }

    public function updateCheckInDays(Request $request) {
        $id = $request->input('id');
        $value = intval($request->input('value'));   
        AppUserActivation::where('id', $id)->update(['check_in_days'=>$value]);
       
        return response()->json([
            "success" => true
        ]);
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
        $activation = AppUserActivation::where('id', $id)->first();
        if (!is_null($activation)) {
            // If we're blocking the activation we go in and revoke the token for that installation.
            if (!is_null($activation->token_id)) {
                $token = \App\OauthAccessToken::where('id', $activation->token_id)->first();
                if (!is_null($token)) {
                    $token->revoked = 1;
                    $token->save();
                }
            }
            $activation->delete();
        }
        
        return response('', 204);
    }

    public function update(Request $request, $id) {
        
        // The javascript side/admin UI will not send
        // password or password_verify unless they are
        // intentionally trying to change a user's password.
    
        $input = $request->only(['bypass_quantity', 'bypass_period', 'report_level']);
        AppUserActivation::where('id', $id)->update($input);

        return response('', 204);
    }
    public function show($id)
    {
        return AppUserActivation::where('id', $id)->get();
    }

    /**
     * Process Activation Bypass Request
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function bypass(Request $request) {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required'
        ]);

        Log::info($request);

        // Get Specific Activation with $identifier
        $activation = AppUserActivation::where('identifier', $request->input('identifier'))->first();
        if (!$activation) {
            return response('', 401);
        }

        $bypass_permitted = 0;
        if(is_null($activation->bypass_quantity)) {
            $user_id = $activation->user_id;
            $user = User::where('id', $user_id)->first();

            if($user->group_id == -1) {
                // group is not assigned to user.
                $arr_output = array(
                    "allowed" => false,
                    "message" => "Request denied." 
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
        if ($request->has('check_only')) {
            $arr_output = array(
                "used" => $bypass_used,
                "permitted" => $bypass_permitted,
            );
            return response()->json($arr_output);
        } elseif ( $bypass_permitted > $bypass_used) {

            $activation->bypass_used++;
            $activation->save();

            // status : granted
            $arr_output = array(
                "allowed" => true,
                "message" => "Request granted. Used ". $activation->bypass_used ." out of ". $bypass_permitted .".",
                "used" => $activation->bypass_used,
                "permitted" => $bypass_permitted,
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
                "message" => "Request denied. You have already used  ". $bypass_used ." out of ". $bypass_permitted .".",
                "used" => $bypass_used,
                "permitted" => $bypass_permitted, 
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
