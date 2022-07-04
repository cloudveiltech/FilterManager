<?php

/*
 * Copyright � 2017 Darren Wiebe
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace App\Http\Controllers;

use App\AppUserActivation;
use App\Events\ActivationBypassDenied;
use App\Events\ActivationBypassGranted;
use App\Group;
use App\User;
use App\Utils;
use Illuminate\Http\Request;
use Log;
use Validator;

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
        } else {
            if ($request->has('user_id') || $user_id != null) {
                $user_id = ($user_id != null ? $user_id : $request->has('user_id'));
                $user = User::find($user_id);
                if ($user && $user->activations()) {
                    $activations = $user->activations()->with('deactivation_request')->get();
                    return $activations;
                } else {
                    return response()->json([]);
                }
            }
        }

        $draw = $request->input('draw');
        $start = $request->input('start');
        $length = $request->input('length') ? $request->input('length') : 10;
        $search = $request->input('search')['value'];

        $order = $request->input('order')[0]['column'];
        $order_name = $request->input('columns')[intval($order)]['data'];
        $order_str = $request->input('order')[0]['dir'];

        $recordsTotal = AppUserActivation::count();
        $query = AppUserActivation::leftJoin("users", "users.id", "=", "app_user_activations.user_id")
            ->select('app_user_activations.*', 'users.name')
            ->when($search, function ($query) use ($search) {
                return $query->where('users.name', 'like', "%$search%")
                    ->orWhere('users.email', 'like', "%$search%")
                    ->orWhere('app_user_activations.device_id', 'like', "%$search%")
                    ->orWhere('app_user_activations.identifier', 'like', "%$search%")
                    ->orWhere('app_user_activations.friendly_name', 'like', "%$search%")
                    ->orWhere('app_user_activations.ip_address', 'like', "%$search%");

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
            "data" => $rows,
        ]);

    }

    public function updateReport(Request $request) {
        $id = $request->input('id');
        $value = intval($request->input('value')); //0 or 1

        $id_arr = explode("_", $id);
        if ($id_arr[0] != "useractivation") {
            return response()->json([
                "success" => false,
            ]);
        }
        $activation_id = intval($id_arr[2]);
        AppUserActivation::where('id', $activation_id)->update(['report_level' => $value]);

        return response()->json([
            "success" => true,
        ]);
    }

    public function updateAlert(Request $request) {
        $id = $request->input('id');
        $value = intval($request->input('value')); //0 or 1
        AppUserActivation::where('id', $id)->update(['alert_partner' => $value]);
        return response()->json([
            "success" => true,
        ]);
    }

    public function updateCheckInDays(Request $request) {
        $id = $request->input('id');
        $value = intval($request->input('value'));
        AppUserActivation::where('id', $id)->update(['check_in_days' => $value]);

        return response()->json([
            "success" => true,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
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
     * @param int $id
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
        $user = $request->user();
        $activation = AppUserActivation::where('id', $id)->first();

        if (!$user->can('all')) {
            if (!$user->can('manage-own-activations')) {
                return response(json_encode(['error' => 'You do not have permission to manage your own activations.']), 400);
            } else {
                if ($activation->user_id != $user->id) {
                    // Can manage own activations.
                    return response(json_encode(['error' => 'You only have permission to manage your own activations.']), 400);
                }
            }
        }

        $fields = ['config_override', 'group_id', 'bypass_used', 'friendly_name'];

        if ($user->can(['all', 'manage-checkin-days'])) {
            $fields[] = 'check_in_days';
        }

        if ($user->can(['all', 'manage-relaxed-policy'])) {
            $fields[] = 'bypass_quantity';
            $fields[] = 'bypass_period';
        }

        if ($user->can(['all', 'set-activation-report-level'])) {
            $fields[] = 'report_level';
        }

        $input = $request->only($fields);

        if (isset($input['config_override'])) {
            $input['config_override'] = Utils::purgeNullsFromJSONSelfModeration($input['config_override']);
        }
        if(key_exists("friendly_name", $input) && $input['friendly_name'] == null) {
            $input["friendly_name"] = "";
        }

        AppUserActivation::where('id', $id)->update($input);

        return response('', 204);
    }

    public function show($id) {
        return AppUserActivation::where('id', $id)->get();
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

                $user_config_str = $user->config_override;
                $app_cfg_str = $group->app_cfg;

                $user_config = json_decode($user_config_str);
                $app_cfg = json_decode($app_cfg_str);

                if (!is_null($user_config)) {
                    foreach ($user_config as $key => $value) {
                        $app_cfg->$key = $value;
                    }
                }

                if (is_null($app_cfg->BypassesPermitted)) {
                    $bypass_permitted = 0;
                } else {
                    $bypass_permitted = $app_cfg->BypassesPermitted;
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
