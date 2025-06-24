<?php

/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace App\Models;

use App\Casts\Json;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Laravel\Passport\HasApiTokens;
use Zizaco\Entrust\Traits\EntrustUserTrait;

class UserActivationAttemptResult
{
    const Success = 1;
    const ActivationLimitExceeded = 2;
    const AccountDisabled = 3;
    const GroupDisabled = 4;
    const IndentifyingInformationMissing = 5;
    const UnknownError = 6;

}

class User extends Authenticatable
{
    use CrudTrait;

    use Notifiable;
    use EntrustUserTrait;
    use HasApiTokens;

    public $timestamps = true;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['activations_used'];


    protected function casts(): array
    {
        return [
            'config_override' => Json::class,
        ];
    }

    public function getConfigAttribute()
    {
        return [$this->config_override];
    }

    public function setConfigAttribute($value)
    {
        $this->config_override = $value[0];
    }

    public function getBlockedSitesAttribute()
    {
        return $this->mapConfigArrayToNameValue("SelfModeration");
    }

    public function setBlockedSitesAttribute($value)
    {
        $this->assignConfigArray("SelfModeration", $value);
    }

    public function getAllowedSitesAttribute()
    {
        return $this->mapConfigArrayToNameValue("CustomWhitelist");
    }

    public function setAllowedSitesAttribute($value)
    {
        $this->assignConfigArray("CustomWhitelist", $value);
    }

    public function getBypassableSitesAttribute()
    {
        return $this->mapConfigArrayToNameValue("CustomBypasslist");
    }

    public function setBypassableSitesAttribute($value)
    {
        $this->assignConfigArray("CustomBypasslist", $value);
    }

    public function getBlockedTriggersAttribute()
    {
        return $this->mapConfigArrayToNameValue("CustomTriggerBlacklist");
    }

    public function setBlockedTriggersAttribute($value)
    {
        $this->assignConfigArray("CustomTriggerBlacklist", $value);
    }

    public function getBlockedApplicationsAttribute()
    {
        return $this->mapConfigArrayToNameValue("CustomBlockedApps");
    }

    public function setBlockedApplicationsAttribute($value)
    {
        $this->assignConfigArray("CustomBlockedApps", $value);
    }

    private function mapConfigArrayToNameValue($arrayKey)
    {
        $array = $this->config_override[$arrayKey] ?? [];
        $result = [];
        foreach ($array as $v) {
            $result[] = ["name" => $v];
        }
        return $result;
    }

    private function assignConfigArray($arrayKey, $value)
    {
        $config = $this->config_override;
        $valueArray = json_decode($value, true);
        $res = [];
        if (is_array($valueArray)) {
            foreach ($valueArray as $value) {
                $res[] = $value["name"];
            }
        }
        $config[$arrayKey] = $res;
        $this->config_override = $config;
    }

    private static $defaultTimeRestrictions = [
        "monday" => [
            "EnabledThrough" => ["00.00", "24.00"],
            "RestrictionsEnabled" => false
        ],
        "tuesday" => [
            "EnabledThrough" => ["00.00", "24.00"],
            "RestrictionsEnabled" => false
        ],
        "wednesday" => [
            "EnabledThrough" => ["00.00", "24.00"],
            "RestrictionsEnabled" => false
        ],
        "thursday" => [
            "EnabledThrough" => ["00.00", "24.00"],
            "RestrictionsEnabled" => false
        ],
        "friday" => [
            "EnabledThrough" => ["00.00", "24.00"],
            "RestrictionsEnabled" => false
        ],
        "saturday" => [
            "EnabledThrough" => ["00.00", "24.00"],
            "RestrictionsEnabled" => false
        ],
        "sunday" => [
            "EnabledThrough" => ["00.00", "24.00"],
            "RestrictionsEnabled" => false
        ],
    ];

    public function getTimeRestrictionsAttribute()
    {
        $config = $this->config_override["TimeRestrictions"] ?? User::$defaultTimeRestrictions;
        $res = [];
        foreach ($config as $key => $value) {
            $field = [
                "day" => $key,
                "from" => date('H:i', strtotime(str_replace(".", ":", $value["EnabledThrough"][0]))),
                "to" => date('H:i', strtotime(str_replace(".", ":", $value["EnabledThrough"][1]))),
                "enabled" => $value["RestrictionsEnabled"] ?? false
            ];
            if ($field["to"] == "00:00") {
                $field["to"] = "23:59";
            }
            $res [] = $field;
        }
        return $res;
    }

    public function setTimeRestrictionsAttribute($value)
    {
        $config = $this->config_override;
        $res = [];
        $days = array_keys(User::$defaultTimeRestrictions);
        foreach ($value as $key => $dayData) {
            $day = $days[$key];
            $from = $this->convertTimeToTimeRestrictionFormat($dayData["from"]);
            $to = $this->convertTimeToTimeRestrictionFormat($dayData["to"]);
            $enabled = $dayData["enabled"];

            $res[$day] = [
                "EnabledThrough" => [
                    $from, $to
                ],
                "RestrictionsEnabled" => (bool)$enabled
            ];
        }

        $isDefaultTimeRestrictions = $this->isDefaultTimeRestrictions($res);
        if($isDefaultTimeRestrictions) {
            unset($config["TimeRestrictions"]);
        } else {
            $config["TimeRestrictions"] = $res;
        }

        $this->config_override = $config;
    }

    private function convertTimeToTimeRestrictionFormat($v)
    {
        $timeParts = explode(":", $v);
        if ($timeParts[0] == 23 && $timeParts[1] == 59) {
            return "24.00";
        }
        return $timeParts[0] . "." . $timeParts[1];
    }

    private function isDefaultTimeRestrictions($res) {
        return json_encode($res) == json_encode(User::$defaultTimeRestrictions);
    }

    protected static function boot()
    {
        parent::boot();

        // Order by name ASC
        static::addGlobalScope('order', function (Builder $builder) {
            $builder->orderBy('name', 'asc');
        });
    }

    public function group()
    {
        return $this->belongsTo('App\Models\Group');
    }

    public function roles()
    {
        return $this->belongsToMany('App\Models\Role');
    }

    public function permissions()
    {
        return $this->hasMany('App\Models\Permission');
    }

    public function activations()
    {
        return $this->hasMany('App\Models\AppUserActivation');
    }

    public function findActivationById($identifier)
    {
        foreach ($this->activations as $activation) {
            if ($activation->identifier == $identifier) {
                return $activation;
            }
        }
        return null;
    }

    /**
     * Gets a count of all activations for this user.
     */
    public function activationsCountRelation()
    {
        return $this->hasOne('App\Models\AppUserActivation')
            ->selectRaw('user_id, count(*) as count')
            ->where('updated_at', '>=', Carbon::now()->subDays(config('app.license_expiration'))->format('Y-m-d H:i:s'))
            ->groupBy('user_id');
    }

    /**
     * Gets the number of rule entries for this filter list.
     */
    public function getActivationsUsedAttribute(): int
    {

        $activationRelation = $this->activationsCountRelation()->first();
        //Log::debug($activationRelation);
        if (!is_null($activationRelation)) {
            return $activationRelation->count;
        }

        return 0;
    }


    public function tryActivateUser(Request $request)
    {
        return $this->tryActivateUserArray($request->all());
    }

    /**
     * Attempts to activate the user or retrieve an existing activation
     * for the user from the given request.
     * @param Request $request
     * @return \App\Models\UserActivationAttemptResult
     */
    public function tryActivateUserArray($params)
    {
        if ($this->isactive == false) {
            return UserActivationAttemptResult::AccountDisabled;
        }

        $userGroup = $this->group()->first();
        if (!is_null($userGroup)) {
            if ($userGroup->isactive == false) {
                return UserActivationAttemptResult::GroupDisabled;
            }
        }

        $validator = Validator::make($params, [
            'identifier' => 'required',
            'device_id' => 'required',
        ]);

        if ($validator->fails()) {
            return UserActivationAttemptResult::IndentifyingInformationMissing;
        }

        $userInfo = ["identifier" => $params['identifier'], "device_id" => $params['device_id']];
        $userInfo['user_id'] = $this->id;
        $userInfo['platform_name'] = $params["os"] ?? "WIN";

        try {
            $activation = AppUserActivation::firstOrCreate($userInfo);
            Log::debug('Created New Activation');
            Log::debug($activation);
            $numActivations = $this->getActivationsUsedAttribute();

            // Only deny this user if their activation is brand new
            // and it pushed the number of activations over the allowed
            // maximum.
            if ($numActivations > $this->activations_allowed + config('app.license_overage_allowed') && $activation->wasRecentlyCreated) {
                $activation->delete();
                return UserActivationAttemptResult::ActivationLimitExceeded;
            }

            // Update timestamp on this user's access. This can be used to
            // track/identify stale activations.
            $activation->touch();

        } catch (Exception $ex) {
            Log::error(print_r($ex, true));
            return UserActivationAttemptResult::UnknownError;
        }

        return UserActivationAttemptResult::Success;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
        'isactive', 'group_id', 'activations_allowed',
        'customer_id', 'config_override', 'relaxed_policy_passcode',
        'config', 'enable_relaxed_policy_passcode', 'blocked_sites', 'allowed_sites',
        'bypassable_sites', 'blocked_triggers', 'blocked_applications', 'time_restrictions'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];
}
