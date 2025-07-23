<?php

/*
 * Copyright © 2017 Jesse Nicholson, 2019 CloudVeil Technology, Inc.
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace App\Http\Controllers;

use App\Events\DeactivationRequestReceived;
use App\Models\AppUserActivation;
use App\Models\DeactivationRequest;
use App\Models\FilterList;
use App\Models\FilterRulesManager;
use App\Models\Helpers\Utils;
use App\Models\Role;
use App\Models\SystemPlatform;
use App\Models\Traits\TimerRestrictionsTrait;
use App\Models\User;
use App\Models\UserActivationAttemptResult;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller {
    const ID_ACTIVATION_ALL = "ALL";


    /**
     * Get information about the currently applied user data. This includes
     * filter rules and configuration data.
     *
     * @return \Illuminate\Http\Response
     */
    public function checkUserData(Request $request) {
        $thisUser = \Auth::user();
        $token = $thisUser->token();
        $activation = $this->getAndTouchActivation($thisUser, $request, $token);

        $userGroup = $activation->group;
        if ($userGroup == null) {
            $userGroup = $thisUser->group()->first();
        }

        if (!is_null($userGroup)) {
            if (!is_null($userGroup->data_sha1) && strcasecmp($userGroup->data_sha1, 'null') != 0) {
                return $userGroup->data_sha1;
            }
        }
        return response('', 204);
    }

    public function rebuildRules(Request $request) {
        $globalFilterRules = new FilterRulesManager();

        $globalFilterRules->buildRuleData();

        return response('', 204);
    }

    /**
     * Return the SHA1 hash of the rule zip. This is for versions >=1.7
     *
     * @return \Illuminate\Http\Response
     */
    /*public function checkRules(Request $request) {
        $globalFilterRules = new FilterRulesManager();

        $dataPath = $globalFilterRules->getRuleDataPath();

        // TODO: Add an SHA1 cache mechanism. Do we want to do this in the DB or in the FS?
        if(file_exists($dataPath)) {
            $hash = hash("sha1", $dataPath);
            return $hash;
        }

        return response('', 204);
    }*/

    public function checkRules(Request $request) {
        $array = $request->all();
        $responseArray = [];

        $thisUser = \Auth::user();
        //Log::debug($request);
        $token = $thisUser->token();
        $this->getAndTouchActivation($thisUser, $request, $token);

        // format of each array key: /{namespace}/{category}/{type}.txt
        foreach ($array as $key => $value) {
            if ($key == 'identifier' || $key == 'device_id' || $key == 'os' || $key == 'identifier_2' || $key == "device_id_2") {
                continue;
            }

            $keyTrimmed = trim($key, '/');
            $keyParts = explode('/', $keyTrimmed);
            if(count($keyParts) != 3) {
                continue;
            }

            $namespace = $keyParts[0];
            $category = $keyParts[1];
            $type = explode('.', $keyParts[2])[0];
            $internalType = $this->getInternalType($type);

            $filterList = FilterList::where('namespace', $namespace)
                ->where('category', $category)
                ->where('type', $internalType)
                ->first();

            if ($filterList == null) {
                $responseArray[$key] = null;
            } else {
                if (strtolower($filterList->file_sha1) === $value) {
                    $responseArray[$key] = true;
                } else {
                    $responseArray[$key] = false;
                }
            }
        }

        return response()->json($responseArray);
    }

    public function changePassword(Request $request) {
        $user = \Auth::user();

        if (!$request->has('current_password') || $request->input('current_password') == null || strlen($request->input('current_password')) == 0) {
            return response()->json([
                'error' => 'Current password not filled out'
            ], 400);
        }

        if (!$request->has('new_password') || $request->input('new_password') == null || strlen($request->input('new_password')) < 4) {
            return response()->json([
                'error' => 'The new password you entered should be filled out and longer than 3 characters'
            ], 400);
        }

        if (!Hash::check($request->input('current_password'), $user->password)) {
            return response()->json([
                'error' => 'The current password that you entered does not match your password'
            ], 400);
        }

        $user->password = Hash::make($request->input('new_password'));

        $user->save();
    }

    /**
     * Request the current user data. This includes filter rules and
     * configuration data.  This is for versions <=1.6.  Version 1.7
     * changes the way configuration is handled.
     *
     * @return \Illuminate\Http\Response
     */
    public function getUserData(Request $request) {
        $this->validate($request, [
            'identifier' => 'required',
            'device_id' => 'required'
        ]);
        $thisUser = \Auth::user();
        //Log::debug($request);
        $token = $thisUser->token();
        $activation = $this->getAndTouchActivation($thisUser, $request, $token);

        $userGroup = $activation->group;
        if ($userGroup == null) {
            $userGroup = $thisUser->group()->first();
        }

        if (!is_null($userGroup)) {
            $groupDataPayloadPath = $userGroup->getGroupDataPayloadPath();
            if (file_exists($groupDataPayloadPath) && filesize($groupDataPayloadPath) > 0) {
                return response()->download($groupDataPayloadPath);
            }
        }

        return response('', 204);
    }

    private function getInternalType($type) {
        $internalType = null;
        switch ($type) {
            case 'rules':
                $internalType = 'Filters';
                break;

            case 'triggers':
                $internalType = 'Triggers';
                break;
        }

        return $internalType;
    }

    public function getRuleset(Request $request, $namespace, $category, $type) {
        $filterRulesManager = new FilterRulesManager();

        // Get etag from request and compare it against the cached file SHA1 for the matched ruleset.
        $etag = $request->header('ETag');

        $internalType = $this->getInternalType($type);
        if ($internalType == null) {
            return response('No such type defined', 500);
        }

        $filterList = FilterList::where('namespace', $namespace)
            ->where('category', $category)
            ->where('type', $internalType)
            ->first();

        if ($filterList === null) {
            return response('', 404);
        }

        if ($etag !== null && strtolower($etag) === strtolower($filterList->file_sha1)) {
            return response('', 304);
        }

        // If they don't match, load the ruleset from the disk cache.
        $hashExists = strlen($filterList->file_sha1) > 0;

        $rulesetFilePath = $filterRulesManager->getRulesetPath($namespace, $category, $type);

        if ($hashExists && file_exists($rulesetFilePath) && filesize($rulesetFilePath) > 0) {
            $response = response()->download($rulesetFilePath);

            $serverEtag = $filterRulesManager->getEtag($rulesetFilePath);
            $response->setEtag($serverEtag);

            return $response;
        } else {
            return response('Ruleset file is broken', 500);
        }
    }

    public function getRules(Request $request) {
        // POST should be a key-value pair list that has the following attributes
        // It should be in the format
        /*
        { "lists": ["/default/adult_pornography/rules", "/default/adult_gambling/rules", ...] }
        */

        $t = microtime(true);
        $post = $request->json()->all();

        $lists = $post['lists'];

        $responseArray = [];

        $filterRulesManager = new FilterRulesManager();

        foreach ($lists as $listName) {
            $trimmed = trim($listName, '/');
            $nameParts = explode('/', $trimmed);

            $namespace = $nameParts[0];
            $category = $nameParts[1];
            $type = explode('.', $nameParts[2])[0];
            $internalType = $this->getInternalType($type);
            if ($internalType == null) {
                return response("No such type defined", 500);
            }

            $filterList = FilterList::where('namespace', $namespace)
                ->where('category', $category)
                ->where('type', $internalType)
                ->first();

            $responseArray[] = '--startlist ' . $listName;
            if ($filterList === null) {
                $responseArray[] = "http-result 404";
            } else {
                // If the SHA hashes don't match, load the ruleset from the disk cache.
                $hashExists = strlen($filterList->file_sha1) > 0;
                $rulesetFilePath = $filterRulesManager->getRulesetPath($namespace, $category, $type);

                if (!$hashExists || !file_exists($rulesetFilePath)) {
                    $responseArray[] = "http-result 404";
                } else {
                    $rulesetFileContents = file_get_contents($rulesetFilePath);
                    $responseArray[] = $rulesetFileContents;
                }
            }

            $responseArray[] = '--endlist';
        }

        $dt = microtime(true) - $t;
        return response(implode("\n", $responseArray))->header('Content-Type', 'text/plain')->header("X-Time-Sec", $dt);
    }

    private function mergeConfigurations($userGroup, $thisUser, $activation) {
        if (!is_null($userGroup)) {
            if ($userGroup->config_cache == null || strlen($userGroup->config_cache) == 0) {
                $userGroup->rebuildGroupData();
            }

            $groupConfiguration = json_decode($userGroup->config_cache, true) ?? [];
            $userConfiguration = $thisUser->config_override ?? [];
            $activationConfiguration = $activation->config_override ?? [];

            $activationConfiguration = Utils::purgeNulls($activationConfiguration);

            $configuration = array_merge($groupConfiguration, $userConfiguration, $activationConfiguration);

            /* -------------------------------------------------------------- */
            /*                 Merge the arrays for these keys                */
            /* -------------------------------------------------------------- */
            $properties = [
                'SelfModeration',
                'CustomWhitelist',
                'CustomBypasslist',
                'CustomTriggerBlacklist',
                'CustomBlockedApps'
            ];

            foreach ($properties as $property) {
                $configuration[$property] = [];
                // merge the arrays, remove duplicates and reset the keys
                $configuration[$property] = array_values(array_unique(array_merge($userConfiguration[$property] ?? [], $activationConfiguration[$property] ?? [])));
            }

            $configuration = Utils::purgeNullsFromSelfModerationArrays($configuration);

            $configuration['CustomTriggerBlacklist'] = array_map("strtolower", $configuration['CustomTriggerBlacklist']);

            $configuration['DebugEnabled'] = 0;
            if($activation->debug_enabled) {
                $configuration['DebugEnabled'] = $activation->debug_enabled;
            } else if(isset($userConfiguration["DebugEnabled"])) {
                $configuration['DebugEnabled'] = $userConfiguration["DebugEnabled"];
            } else if(isset($groupConfiguration["DebugEnabled"])) {
                $configuration['DebugEnabled'] = $groupConfiguration["DebugEnabled"];
            }

            if ($activation->bypass_quantity) {
                $configuration['BypassesPermitted'] = $activation->bypass_quantity;
            }

            if ($activation->bypass_period) {
                $configuration['BypassDuration'] = $activation->bypass_period;
            }


            if ($thisUser->enable_relaxed_policy_passcode) {
                $configuration['EnableRelaxedPolicyPasscode'] = $thisUser->enable_relaxed_policy_passcode;
            }

            $configuration["FriendlyName"] = $activation->friendly_name;

            if(!isset($configuration['BypassesPermitted'])) {
                $configuration['BypassesPermitted'] = 0;
            }
            if(!isset($configuration['BypassDuration'])) {
                $configuration['BypassDuration'] = 0;
            }

            if(isset($configuration["TimeRestrictionsTemplates"])) {
                $configuration["TimeRestrictions"] = AppUserActivation::applyTemplates($configuration["TimeRestrictions"], $configuration["TimeRestrictionsTemplates"]);
                unset($configuration["TimeRestrictionsTemplates"]);
            }
            return $configuration;
        } else {
            return null;
        }
    }

    /**
     * Request the current activation configuration.
     * This is for versions >=1.7.
     *
     * @return \Illuminate\Http\Response
     */
    public function getConfig(Request $request) {
        $this->validate($request, [
            'identifier' => 'required',
            'device_id' => 'required'
        ]);
        $thisUser = \Auth::user();
        //Log::debug($request);
        $token = $thisUser->token();
        $activation = $this->getAndTouchActivation($thisUser, $request, $token);
        $userGroup = $activation->group;
        if ($userGroup == null) {
            $userGroup = $thisUser->group()->first();
        }

        $configuration = $this->mergeConfigurations($userGroup, $thisUser, $activation);

        $configuration["BlacklistedApplications"] = $this->filterAppCollectionByPlatform($configuration["BlacklistedApplications"], $activation->platform_name);
        $configuration["WhitelistedApplications"] = $this->filterAppCollectionByPlatform($configuration["WhitelistedApplications"], $activation->platform_name);
        $configuration["BlockedApplications"] = $this->filterAppCollectionByPlatform($configuration["BlockedApplications"], $activation->platform_name);

        if (!is_null($configuration)) {
            return $configuration;
        } else {
            return response('', 204);
        }
    }

    private function filterAppCollectionByPlatform(&$collection, $platform) {
        $newCollection = [];
        foreach ($collection as $item) {
            if($item["os"] == $platform) {
                $newCollection[] = $item["name"];
            }
        }
        return $newCollection;
    }

    /**
     * Request the checksum for current activation configuration.
     * This is for versions >=1.7.
     *
     * @return \Illuminate\Http\Response
     */
    public function checkConfig(Request $request) {
        $this->validate($request, [
            'identifier' => 'required',
            'device_id' => 'required'
        ]);
        $thisUser = \Auth::user();
        //Log::debug($request);
        $token = $thisUser->token();
        $activation = $this->getAndTouchActivation($thisUser, $request, $token);
        $userGroup = $activation->group;
        if ($userGroup == null) {
            $userGroup = $thisUser->group()->first();
        }

        $configuration = $this->mergeConfigurations($userGroup, $thisUser, $activation);

        $configuration["BlacklistedApplications"] = $this->filterAppCollectionByPlatform($configuration["BlacklistedApplications"], $activation->platform_name);
        $configuration["WhitelistedApplications"] = $this->filterAppCollectionByPlatform($configuration["WhitelistedApplications"], $activation->platform_name);
        $configuration["BlockedApplications"] = $this->filterAppCollectionByPlatform($configuration["BlockedApplications"], $activation->platform_name);

        if (!is_null($configuration)) {
            return ['sha1' => sha1(json_encode($configuration))];
        } else {
            return response('', 204);
        }
    }

    /**
     * The current authenticated user is requesting an app deactivation.
     *
     * @return \Illuminate\Http\Response
     */
    public function getCanUserDeactivate(Request $request) {

        $validator = Validator::make($request->all(), [
            'identifier' => 'required',
            'device_id' => 'required',
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
                } catch (\Exception $e) {
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
            'device_id' => 'required',
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
                        'userEmail' => $user->email,
                    ], 200);
                } else {
                    // User is not active.km
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
            case UserActivationAttemptResult::Success:
                {
                    // Creating a token without scopes...
                    $token = $user->createToken('Token Name')->accessToken;
                    $this->checkUserData($request);
                    return $token;
                }
                break;

            case UserActivationAttemptResult::ActivationLimitExceeded:
                {
                    Auth::logout();
                    return response('Your account has been activated on more devices than permitted.', 401);
                }
                break;

            case UserActivationAttemptResult::AccountDisabled:
                {
                    Auth::logout();
                    return response('Your account has been disabled.', 401);
                }
                break;

            case UserActivationAttemptResult::GroupDisabled:
                {
                    Auth::logout();
                    return response('The group that your account belongs to has been disabled.', 401);
                }
                break;

            case UserActivationAttemptResult::IndentifyingInformationMissing:
                {
                    Auth::logout();
                    return response('User device identifier and or name not supplied.', 401);
                }
                break;

            case UserActivationAttemptResult::UnknownError:
                {
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

    private function getAndTouchActivation(User $user, Request $request, $token) {
        // If we receive an identifier, and we always should, then we touch the updated_at field in the database to show the last contact time.
        // If the identifier doesn't exist in the system we create a new activation.
        if ($request->has('identifier')) {
            $input = $request->input();

            $os = SystemPlatform::PLATFORM_SUPPORTED[0];
            if (!empty($input["os"]) && in_array($input["os"], SystemPlatform::PLATFORM_SUPPORTED)) {
                $os = $input["os"];
            }

            $args = [$input['identifier']];
            $whereStatement = "identifier = ?";

            if (!empty($input['device_id'])) {
                $whereStatement .= " and device_id = ?";
                $args[] = $input['device_id'];
            }

            // Get Specific Activation with $identifier
            $activation = AppUserActivation::whereRaw($whereStatement, $args)->first();
            if (!$activation && $request->has('identifier_2') && $request->has('device_id_2')) {//identifier_2 is passed in case we changed device name locally
                $args = [$input['identifier_2'], $input['device_id_2']];
                $activation = AppUserActivation::whereRaw($whereStatement, $args)->first();
                if ($activation) {
                    //update info
                    $activation->identifier = $input['identifier'];
                    $activation->device_id = $input['device_id'];
                    $activation->save();
                }
            }

            $hasAppVersion = $request->has('app_version');

            if ($activation) {
                if ($hasAppVersion) {
                    $activation->app_version = $request->input('app_version');
                }

                $activation->updated_at = Carbon::now()->timestamp;
                $activation->last_sync_time = Carbon::now();
                $activation->ip_address = $request->ip();
                if ($token) {
                    $activation->token_id = $token->id;
                }

                $activation->platform_name = $os;
                $activation->save();
                //Log::debug('Activation Exists.  Saved');
            } else {
                $activation = new AppUserActivation;
                $activation->updated_at = Carbon::now()->timestamp;
                $activation->last_sync_time = Carbon::now();
                $activation->app_version = $hasAppVersion ? $request->input('app_version') : 'none';
                $activation->user_id = $user->id;
                $activation->device_id = $request->input('device_id');
                $activation->identifier = $request->input('identifier');
                $activation->ip_address = $request->ip();
                $activation->platform_name = $os;
                if ($token) {
                    $activation->token_id = $token->id;
                }
                $activation->bypass_used = 0;
                $activation->save();
            }
            return $activation;
        }
    }

    /**
     * response server time
     * @return [ server_time => '2018-07-24T18:58:04Z' ]
     */
    public function getTime() {
        $time = [
            "server_time" => date('Y-m-d\Th:i:s\Z')
        ];
        return response($time, 200);
    }

    public function getRelaxedPolicyPasscode() {
        $user = \Auth::user();

        $result = [
            'enable_relaxed_policy_passcode' => $user->enable_relaxed_policy_passcode,
            'relaxed_policy_passcode' => $user->relaxed_policy_passcode
        ];

        if ($user->can(['all', 'manage-relaxed-policy'])) {
            $config = json_decode($user->config_override);

            if (json_last_error() == JSON_ERROR_NONE) {
                $result['bypasses_permitted'] = isset($config->BypassesPermitted) ? $config->BypassesPermitted : null;
                $result['bypass_duration'] = isset($config->BypassDuration) ? $config->BypassDuration : null;
            }
        }

        return $result;
    }

    public function setRelaxedPolicyPasscode(Request $request) {
        $user = \Auth::user();

        if ($request->has('enable_relaxed_policy_passcode')) {
            $user->enable_relaxed_policy_passcode = $request->input('enable_relaxed_policy_passcode');
        }

        if ($request->has('relaxed_policy_passcode')) {
            $user->relaxed_policy_passcode = $request->input('relaxed_policy_passcode');
        }

        if ($user->can(['all', 'manage-relaxed-policy'])) {
            $config = json_decode($user->config_override);

            if (json_last_error() != JSON_ERROR_NONE) {
                $config = new \stdClass();
            }

            if ($request->has('bypasses_permitted')) {
                $config->BypassesPermitted = $request->input('bypasses_permitted');
            }

            if ($request->has('bypass_duration')) {
                $config->BypassDuration = $request->input('bypass_duration');
            }

            $user->config_override = json_encode($config);
        }

        $user->save();

        return '{}';
    }

    public function getSelfModerationInfo(Request $request) {
        $user = \Auth::user();

        $config = json_decode($user->config_override);

        $data = [];

        if (json_last_error() != JSON_ERROR_NONE) {
            $data['whitelist'] = [];
            $data['blacklist'] = [];
            $data['triggerBlacklist'] = [];
            $data['appBlockList'] = [];
        } else {
            $data['whitelist'] = $this->fillSelfModerationArray([], isset($config->CustomWhitelist) ? $config->CustomWhitelist : [], self::ID_ACTIVATION_ALL, self::ID_ACTIVATION_ALL);
            $data['blacklist'] = $this->fillSelfModerationArray([], isset($config->SelfModeration) ? $config->SelfModeration : [], self::ID_ACTIVATION_ALL, self::ID_ACTIVATION_ALL);
            $data['triggerBlacklist'] = $this->fillSelfModerationArray([], isset($config->CustomTriggerBlacklist) ? $config->CustomTriggerBlacklist : [], self::ID_ACTIVATION_ALL, self::ID_ACTIVATION_ALL);
            $data['appBlockList'] = $this->fillSelfModerationArray([], isset($config->CustomBlockedApps) ? $config->CustomBlockedApps : [], self::ID_ACTIVATION_ALL, self::ID_ACTIVATION_ALL);
        }

        $activations = $user->activations;
        foreach ($activations as $activation) {
            $activationConfig = json_decode($activation->config_override);

            if (!empty($activationConfig->CustomWhitelist)) {
                $data['whitelist'] = $this->fillSelfModerationArray($data['whitelist'], $activationConfig->CustomWhitelist, $activation->identifier, $activation->device_id);
            }

            if (!empty($activationConfig->SelfModeration)) {
                $data['blacklist'] = $this->fillSelfModerationArray($data['blacklist'], $activationConfig->SelfModeration, $activation->identifier, $activation->device_id);
            }

            if (!empty($activationConfig->CustomTriggerBlacklist)) {
                $data['triggerBlacklist'] = $this->fillSelfModerationArray($data['triggerBlacklist'], $activationConfig->CustomTriggerBlacklist, $activation->identifier, $activation->device_id);
            }
            if (!empty($activationConfig->CustomBlockedApps)) {
                $data['appBlockList'] = $this->fillSelfModerationArray($data['appBlockList'], $activationConfig->CustomBlockedApps, $activation->identifier, $activation->device_id);
            }
        }

        $data['whitelist'] = array_values($data["whitelist"]);
        $data['blacklist'] = array_values($data["blacklist"]);
        $data['triggerBlacklist'] = array_values($data["triggerBlacklist"]);
        $data['appBlockList'] = array_values($data["appBlockList"]);

        return $data;
    }

    private function fillSelfModerationArray($result, $newData, $activationKey, $activationName) {
        foreach ($newData as $value) {
            $result[] = [
                "value" => $value,
                "activation" => $activationKey
            ];
        }
        return $result;
    }

    public function addSelfModeratedWebsite(Request $request) {
        $user = \Auth::user();

        $token = $user->token();
        $activation = $this->getAndTouchActivation($user, $request, $token);

        $config = json_decode($activation->config_override);

        if (json_last_error() != JSON_ERROR_NONE) {
            $config = new \stdClass();
        }

        if (!isset($config->SelfModeration)) {
            $config->SelfModeration = [];
        }

        $listType = 'blacklist';
        if ($request->has('list_type')) {
            $listType = $request->input('list_type');
        }

        if ($listType == 'whitelist') {
            if ($user->can(['all', 'manage-whitelisted-sites'])) {
                if (!isset($config->CustomWhitelist)) {
                    $config->CustomWhitelist = [];
                }

                if ($request->has('url') && !empty($request->input('url'))) {
                    $config->CustomWhitelist[] = $request->input('url');
                } else {
                    return response(json_encode(['error' => 'Please specify a URL.']), 400);
                }
            } else {
                return response(json_encode(["error" => "You do not have permission to manage your whitelist"]), 400);
            }
        } else {
            $key = "SelfModeration";
            if ($listType == "triggerBlacklist") {
                $key = "CustomTriggerBlacklist";
            }
            if ($listType == "appBlockList") {
                $key = "CustomBlockedApps";
            }

            if (!isset($config->$key)) {
                $config->$key = [];
            }

            if ($request->has('url') && !empty($request->input('url'))) {
                $config->$key[] = $request->input('url');
            } else {
                return response(json_encode(['error' => 'Please specify a URL.']), 400);
            }
        }

        $activation->config_override = json_encode($config);
        $activation->save();

        return response('', 204);
    }

    public function setSelfModerationInfo(Request $request) {
        $user = \Auth::user();

        if ($user->can(['all', 'manage-whitelisted-sites'])) {
            if ($request->has('whitelist')) {
                $whitelist = Utils::purgeNulls($request->input('whitelist'));
            } else {
                $whitelist = [];
            }

            $this->saveSelfModerationList($whitelist, $user, "CustomWhitelist", FILTER_VALIDATE_DOMAIN);
        }

        if ($request->has('blacklist')) {
            $selfModeration = Utils::purgeNulls($request->input('blacklist'));
        } else {
            $selfModeration = [];
        }
        $this->saveSelfModerationList($selfModeration, $user, "SelfModeration", FILTER_VALIDATE_DOMAIN);

        if ($request->has('triggerBlacklist')) {
            $customTriggerBlacklist = Utils::purgeNulls($request->input('triggerBlacklist'));
        } else {
            $customTriggerBlacklist = [];
        }
        $this->saveSelfModerationList($customTriggerBlacklist, $user, "CustomTriggerBlacklist");

        if ($request->has('appBlockList')) {
            $customBlockedApps = Utils::purgeNulls($request->input('appBlockList'));
        } else {
            $customBlockedApps = [];
        }
        $this->saveSelfModerationList($customBlockedApps, $user, "CustomBlockedApps");

        return '{}';
    }

    private function saveSelfModerationList($list, $user, $confgiKey, $filterVarFlag = FILTER_DEFAULT) {
        /*
         * items should be in the form
         * [
         *   [
         *     "value" => ..
         *     "activation" => ..
         *   ]
         * ]
         */
        $userConfig = json_decode($user->config_override);
        if (json_last_error() != JSON_ERROR_NONE) {
            $userConfig = new \stdClass();
        }
        $perActivationsList = $this->preparePerUserActivationsArray($user);
        $perActivationsList = $this->filterSelfModerationArrays($list, $filterVarFlag, $perActivationsList);

        foreach ($perActivationsList as $key => $list) {
            if ($key == self::ID_ACTIVATION_ALL) {
                $userConfig->{$confgiKey} = $list;
                $user->config_override = json_encode($userConfig);
                $user->save();
            } else {
                $activation = $user->findActivationById($key);
                if ($activation != null) {
                    $config = json_decode($activation->config_override);
                    if (json_last_error() != JSON_ERROR_NONE) {
                        $config = new \stdClass();
                    }
                    $config->{$confgiKey} = $list;
                    $activation->config_override = json_encode($config);
                    $activation->save();
                }
            }
        }
    }

    private function preparePerUserActivationsArray($user) {
        $perActivationsList = [
            self::ID_ACTIVATION_ALL => []
        ];
        $activations = $user->activations;
        foreach ($activations as $activation) {
            $perActivationsList[$activation->identifier] = [];
        }
        return $perActivationsList;
    }

    private function filterSelfModerationArrays($list, $filterVarFlag, $perActivationsList) {
        foreach ($list as $item) {
            $activationId = trim($item['activation']);
            $value = filter_var(trim($item['value']), $filterVarFlag);
            if ($value !== false && !empty($value) && isset($perActivationsList[$activationId])) {//to be sure we set values only for this user's activations
                $perActivationsList[$activationId][] = $value;
            }
        }

        //remove values if there's similar value for "ALL"
        foreach ($list as $item) {
            $activationId = trim($item['activation']);
            $value = filter_var(trim($item['value']), $filterVarFlag);
            if ($activationId != self::ID_ACTIVATION_ALL) { //&& isset($perActivationsList[self::ID_ACTIVATION_ALL][$value])) {//to be sure we set values only for this user's activations
                $perActivationsList[$activationId] = Arr::except($perActivationsList[$activationId], $perActivationsList[self::ID_ACTIVATION_ALL]);
            }
/*            //if ($activationId != self::ID_ACTIVATION_ALL && isset($perActivationsList[self::ID_ACTIVATION_ALL][$value])) {//to be sure we set values only for this user's activations
            //    unset($perActivationsList[$activationId][$value]);
            //}*/
        }

        return $perActivationsList;
    }


    public function getTimeRestrictions() {
        $user = \Auth::user();

        $config = json_decode($user->config_override);

        if (json_last_error() != JSON_ERROR_NONE || !isset($config->TimeRestrictions)) {
            return "{}";
        }

        return json_encode($config->TimeRestrictions);
    }

    public function setTimeRestrictions(Request $request) {
        $user = \Auth::user();

        $config = json_decode($user->config_override);

        if (json_last_error() != JSON_ERROR_NONE) {
            $config = new \stdClass();
        }

        if ($request->has('time_restrictions')) {
            $config->TimeRestrictions = $request->input('time_restrictions');
        }

        if (!isset($config->TimeRestrictions)) {
            $config->TimeRestrictions = [];
        }

        $user->config_override = json_encode($config);
        $user->save();

        return '{}';
    }
}
