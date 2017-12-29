<?php

/*
 * Copyright © 2017 Jesse Nicholson
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
use App\UserActivationAttemptResult;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\DeactivationRequest;
use App\AppUserActivation;
use App\Events\DeactivationRequestReceived;
use Laravel\Passport\Passport;
use Log;
use Carbon\Carbon;

class UserController extends Controller {

    /**
     * Display a listing of the resource.
     * Accepts email as a parameter to search for users.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request) {
        $email = $request->input('email');
        $customer_id = $request->input('customer_id');
        return User::with(['group', 'roles','activations'])
            ->when($email, function($query) use ($email) {
                return $query->where('email', $email);
            })
            ->when($customer_id, function($query) use ($customer_id) {
                return $query->where('customer_id', $customer_id);
            })
            ->get();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        // No forms here kids.
        return response('', 405);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|same:password_verify',
            'role_id' => 'required|exists:roles,id',
            'group_id' => 'required|exists:groups,id'
        ]);

        $input = $request->only(['name', 'email', 'password', 'role_id', 'group_id','customer_id','activations_allowed','isactive']);
        $input['password'] = Hash::make($input['password']);
        
        $user = User::create($input);   

        $suppliedRoleId = $request->input('role_id');
        $suppliedRole = Role::where('id', $suppliedRoleId)->first();
        $user->attachRole($suppliedRole);

        return response('', 204);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
        return User::where('id', $id)->get();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        // There is no form, son.
        return response('', 405);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {

        // The javascript side/admin UI will not send
        // password or password_verify unless they are
        // intentionally trying to change a user's password.

        //Checking customer_id 
        $input_chk_customer_id = $request->only(['customer_id', 'email', 'name']);
        $customer_id = $input_chk_customer_id['customer_id'];
        
        if($customer_id != null) {
            $customer_list = User::where('id', '!=', $id)->where('customer_id', $customer_id)->get();
            $customer_count = count($customer_list);
            if($customer_count > 0) {
                return response('customer_id is duplicated. please choose another customer_id', 403);
            }
        }

        // Checking email address
        $email = $input_chk_customer_id['email'];
        $email_list = User::where('id', '!=', $id)->where('email', $email)->get();
        $email_count = count($email_list);
        if($email_count > 0) {
            return response('email address exists, please choose another email_address', 403);
        }

        // Checking user id
        $name = $input_chk_customer_id['name'];
        $email_list = User::where('id', '!=', $id)->where('name', $name)->get();
        $email_count = count($email_list);
        if($email_count > 0) {
            return response('user_id already exists, please choose another user_id', 403);
        }

        $inclPassword = false;

        if ($request->has('password_verify') && $request->has('password')) {
            $this->validate($request, [
                'name' => 'required',
                'email' => 'required',
                'password' => 'required|same:password_verify'
            ]);

            $inclPassword = true;
        } else {
            $this->validate($request, [
                'name' => 'required',
                'email' => 'required'
            ]);
        }

        $input = $request->except(['password', 'password_verify', 'role_id']);

        if ($inclPassword) {
            $pInput = $request->only(['password', 'password_verify']);
            $input['password'] = bcrypt($pInput['password']);
        }

        User::where('id', $id)->update($input);

        if ($request->has('role_id')) {

            $this->validate($request, [
                'role_id' => 'required|exists:roles,id'
            ]);

            $suppliedRoleId = $request->input('role_id');
            $suppliedRole = Role::where('id', $suppliedRoleId)->first();

            $suppliedUser = User::where('id', $id)->first();
            if (!$suppliedUser->hasRole($suppliedRole)) {
                $suppliedUser->detachRoles();
                $suppliedUser->attachRole($suppliedRole);
            }
        }

        /* 
         * If we are deactivating the user then we revoke all their personal access tokens at the same time.
         * This will force them to redo all installations.
         */

        if ($request->has('isactive')) {
            if ($request->input('isactive') == '0') {
                $updateUser = User::where('id', $id)->first();
                $userTokens = $updateUser->tokens;
                foreach($userTokens as $token) {
                    $token->revoke();   
                }  
            }
        }

        return response('', 204);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        
        $user = User::where('id', $id)->first();
        if (!is_null($user)) {

            // Revoke all tokens.
            $userTokens = $user->tokens;
            foreach($userTokens as $token) {
                $token->revoke();   
            }  

            $user->detachRoles();

            $user->delete();
        }

        return response('', 204);
    }

    /**
     * Get information about the currently applied user data. This includes
     * filter rules and configuration data.
     *
     * @return \Illuminate\Http\Response
     */
    public function checkUserData(Request $request) {
        $thisUser = \Auth::user();
        $token = $thisUser->token();
        // If we receive an identifier, and we always should, then we touch the updated_at field in the database to show the last contact time.
        // If the identifier doesn't exist in the system we create a new activation.
        if ($request->has('identifier')) {
            $activation = AppUserActivation::where('identifier', $request->input('identifier'))->first();
            if($activation) {
                $activation->updated_at = Carbon::now()->timestamp;
                $activation->app_version = $request->has('app_version')?$request->input('app_version'): 'none';
                $activation->ip_address = $request->ip();
                if ($token) {
                    $activation->token_id = $token->id;
                }
                $activation->save();
                //Log::debug('Activation Exists.  Saved'); 
            } else {
                $activation = new AppUserActivation;
                $activation->updated_at = Carbon::now()->timestamp;
                $activation->app_version = $request->has('app_version')?$request->input('app_version'): 'none';                
                $activation->user_id = $thisUser->id;
                $activation->device_id = $request->input('device_id');
                $activation->identifier = $request->input('identifier');
                $activation->ip_address = $request->ip();
                if ($token) {
                    $activation->token_id = $token->id;
                }
                $activation->bypass_used = 0;
                $activation->save();                
            }
        }
        $userGroup = $thisUser->group()->first();
        if (!is_null($userGroup)) {
            if (!is_null($userGroup->data_sha1) && strcasecmp($userGroup->data_sha1, 'null') != 0) {
                return $userGroup->data_sha1;
            }
        }
        return response('', 204);
    }

    /**
     * Request the current user data. This includes filter rules and
     * configuration data.
     *
     * @return \Illuminate\Http\Response
     */
    public function getUserData(Request $request) {
        $thisUser = \Auth::user();
        //Log::debug($request);
        $userGroup = $thisUser->group()->first();
        if (!is_null($userGroup)) {
            $groupDataPayloadPath = $userGroup->getGroupDataPayloadPath();
            if (file_exists($groupDataPayloadPath) && filesize($groupDataPayloadPath) > 0) {
                return response()->download($groupDataPayloadPath);
            }
        }

        return response('', 204);
    }

    /**
     * The current authenticated user is requesting an app deactivation.
     *
     * @return \Illuminate\Http\Response
     */
    public function getCanUserDeactivate(Request $request) {

        $validator = Validator::make($request->all(), [
                    'identifier' => 'required',
                    'device_id' => 'required'
        ]);

        if (!$validator->fails()) {
            $thisUser = \Auth::user();

            $reqArgs = $request->only(['identifier', 'device_id']);

            $reqArgs['user_id'] = $thisUser->id;

            $deactivateRequest = DeactivationRequest::firstOrCreate($reqArgs);

            if ($deactivateRequest->granted == true) {
                // Clean up the deactivation request.
                $deactivateRequest->delete();

                // Remove this user's registration, since they're being
                // granted an uninstall/removal.
                AppUserActivation::where($reqArgs)->delete();

                return response('', 204);
            } else {
              // If this is a deactivate request that has not been granted then we fire an event.
                try {
                    event(new DeactivationRequestReceived($deactivateRequest));
                } catch(\Exception $e){
                    Log::error($e);
                }
            }
        }

        return response('', 401);
    }

    /**
     * Handles when user is requesting their license terms.
     * @param Request $request
     */
    public function getUserTerms(Request $request) {

        $userLicensePath = resource_path() . DIRECTORY_SEPARATOR . 'UserLicense.txt';

        if (file_exists($userLicensePath) && filesize($userLicensePath) > 0) {
            return response()->download($userLicensePath);
        }
        return response('', 500);
    }

    /**
     * Handles when the application has lost it's credentials.  If the activation exists
     * it returns a token and the users email address.
     * @param Request $request
     */
    public function retrieveUserToken(Request $request) {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required',
            'device_id' => 'required'
        ]);

        if (!$validator->fails()) {
            $activation = AppUserActivation::where('identifier', $request->input('identifier'))
                ->where('device_id', $request->input('device_id'))
                ->first();
            if ($activation) {
                // Lookup the user this activation belongs to.
                $user = User::where('id', $activation->user_id)->first();
                if ($user->isactive) {
                    // Creating a token without scopes...
                    $token = $user->createToken('Token Name')->accessToken; 
                    return response([
                        'authToken' => $token,
                        'userEmail' => $user->email
                    ], 200);
                } else {
                    // User is not active.
                    return response('User is not active', 401);
                }
            } else {
                return response('Activation does not exist.', 401);
            }
        }        
        return response($validator->errors(), 401);

    }

    /**
     * Handles when user logs in from the application.  Returns their access token.
     * @param Request $request
     */
    public function getUserToken(Request $request) {
        $user = \Auth::user();

        $userActivateResult = $user->tryActivateUser($request);

        switch ($userActivateResult) {
            case UserActivationAttemptResult::Success: {
                    // Creating a token without scopes...
                    $token = $user->createToken('Token Name')->accessToken; 
                    $this->checkUserData($request); 
                    return $token; 
                }
                break;

            case UserActivationAttemptResult::ActivationLimitExceeded: {
                    Auth::logout();
                    return response('Your account has been activated on more devices than permitted.', 401);
                }
                break;

            case UserActivationAttemptResult::AccountDisabled: {
                    Auth::logout();
                    return response('Your account has been disabled.', 401);
                }
                break;

            case UserActivationAttemptResult::GroupDisabled: {
                    Auth::logout();
                    return response('The group that your account belongs to has been disabled.', 401);
                }
                break;

            case UserActivationAttemptResult::IndentifyingInformationMissing: {
                    Auth::logout();
                    return response('User device identifier and or name not supplied.', 401);
                }
                break;

            case UserActivationAttemptResult::UnknownError: {
                    Auth::logout();
                    return response('An unknown error occurred while trying to activate or verify your account activation.', 401);
                }
                break;
        }
    }

    /**
     * Handles when user request to revoke their personal token.  This should be used to sign out of the appliation.
     * This could probably be rolled into deactivation requests in the future.
     * @param Request $request
     */
    public function revokeUserToken(Request $request) {

        $user = \Auth::user();
        $token = $user->token();
        $token->revoke();
        return response('', 200);
    }

    /**
     * Used by our debugging tool to provide a central place to store logs received from users.
     * @param Request $request
     */
    public function uploadLog(Request $request) {
        $this->validate($request, [
            'user_email' => 'required|email',
            'log' => 'required',
            'source' => 'required',
        ]);
        $path = $request->file('log')->store('user_logs/' . $request->input('user_email'));
        return "OK";
    }

    public function activation_data(Request $request, $id) {
        return AppUserActivation::where('user_id', $id)->get();
    }
}
