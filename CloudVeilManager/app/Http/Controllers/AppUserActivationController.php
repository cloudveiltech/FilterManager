<?php

/*
 * Copyright � 2017 Darren Wiebe
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace App\Http\Controllers;

use App\Events\ActivationBypassDenied;
use App\Events\ActivationBypassGranted;
use App\Models\AppUserActivation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AppUserActivationController extends Controller {

    public function __construct() {

    }

    /**
     * Process Activation Bypass Request
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function bypass(Request $request) {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required',
        ]);

        $input = $request->input();

        $passcodeEnabled = false;
        $passcode = null;

        $args = [$input['identifier']];
        $whereStatement = "identifier = ?";

        if (!empty($input['device_id'])) {
            $whereStatement .= " and device_id = ?";
            $args[] = $input['device_id'];
        }

        // Get Specific Activation with $identifier
        $activation = AppUserActivation::whereRaw($whereStatement, $args)->first();
        if (!$activation) {
            return response('', 401);
        }

        $bypass_permitted = 0;
        if (is_null($activation->bypass_quantity)) {
            $user_id = $activation->user_id;
            $user = User::where('id', $user_id)->first();

            if ($user->group_id == -1) {
                // group is not assigned to user.
                $arr_output = array(
                    "allowed" => false,
                    "message" => "Request denied.",
                );
                return response()->json($arr_output);
            } else {
                // group is assigned
                $group = $user->group()->first();

                $user_config = $user->config_override;
                $app_cfg = $group->app_cfg;

                if (!is_null($user_config)) {
                    foreach ($user_config as $key => $value) {
                        $app_cfg[$key] = $value;
                    }
                }

                if (is_null($app_cfg["BypassesPermitted"])) {
                    $bypass_permitted = 0;
                } else {
                    $bypass_permitted = $app_cfg["BypassesPermitted"];
                }

                if ($user->enable_relaxed_policy_passcode) {
                    $passcodeEnabled = true;
                    $passcode = $user->relaxed_policy_passcode;
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
        } else {
            // Check to see if user has passcode.
            $isAuthorized = false;
            if ($passcodeEnabled) {
                $enteredPasscode = null;
                if ($request->has('passcode')) {
                    $enteredPasscode = $request->input('passcode');
                }

                if ($passcode == $enteredPasscode) {
                    $isAuthorized = true;
                }
            } else {
                $isAuthorized = true;
            }

            if (!$isAuthorized) {
                $arr_output = array(
                    "allowed" => false,
                    "message" => "Request denied. You entered an incorrect passcode.",
                    "used" => $activation->bypass_used,
                    "permitted" => $bypass_permitted
                );

                try {
                    event(new ActivationBypassDenied($activation));
                } catch (\Exception $e) {
                    Log::error($e);
                }

                return response()->json($arr_output);
            } elseif ($bypass_permitted > $bypass_used) {
                $activation->bypass_used++;
                $activation->save();

                // status : granted
                $arr_output = array(
                    "allowed" => true,
                    "message" => "Request granted. Used " . $activation->bypass_used . " out of " . $bypass_permitted . ".",
                    "used" => $activation->bypass_used,
                    "permitted" => $bypass_permitted,
                );

                // Trigger of bypass_granted
                try {
                    event(new ActivationBypassGranted($activation));
                } catch (\Exception $e) {
                    Log::error($e);
                }

                return response()->json($arr_output);
            } else {
                // status: denied
                $arr_output = array(
                    "allowed" => false,
                    "message" => "Request denied. You have already used  " . $bypass_used . " out of " . $bypass_permitted . ".",
                    "used" => $bypass_used,
                    "permitted" => $bypass_permitted,
                );
                // Trigger of bypass_denied
                try {
                    event(new ActivationBypassDenied($activation));
                } catch (\Exception $e) {
                    Log::error($e);
                }

                return response()->json($arr_output);
            }
        }
    }
}
