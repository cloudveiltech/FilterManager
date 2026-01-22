<?php

/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace App\Models;

use App\AppUserActivation;
use App\Casts\Json;
use App\Models\Traits\OverridableConfigTrait;
use App\Models\Traits\TimerRestrictionsTrait;
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

class User extends Authenticatable
{
    use CrudTrait;

    use Notifiable;
    use EntrustUserTrait;
    use HasApiTokens;

    use OverridableConfigTrait;
    use TimerRestrictionsTrait;

    public $timestamps = true;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['activations_used'];

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
     * @return \App\Models\Helpers\UserActivationAttemptResult
     */
    public function tryActivateUserArray($params)
    {
        if ($this->isactive == false) {
            return Helpers\UserActivationAttemptResult::AccountDisabled;
        }

        $userGroup = $this->group()->first();
        if (!is_null($userGroup)) {
            if ($userGroup->isactive == false) {
                return Helpers\UserActivationAttemptResult::GroupDisabled;
            }
        }

        $validator = Validator::make($params, [
            'identifier' => 'required',
            'device_id' => 'required',
        ]);

        if ($validator->fails()) {
            return Helpers\UserActivationAttemptResult::IndentifyingInformationMissing;
        }

        $userInfo = ["identifier" => $params['identifier'], "device_id" => $params['device_id']];
        $userInfo['user_id'] = $this->id;
        $userInfo['platform_name'] = $params["os"] ?? "WIN";

        try {
            $activation = AppUserActivation::withTrashed()->orderBy("last_sync_time", "DESC")->firstOrCreate($userInfo);
            Log::debug('Created New Activation');
            Log::debug($activation);
            $numActivations = $this->getActivationsUsedAttribute();

            // Only deny this user if their activation is brand new
            // and it pushed the number of activations over the allowed
            // maximum.
            if ($numActivations > $this->activations_allowed + config('app.license_overage_allowed') && $activation->wasRecentlyCreated) {
                $activation->delete();
                return Helpers\UserActivationAttemptResult::ActivationLimitExceeded;
            }

            // Update timestamp on this user's access. This can be used to
            // track/identify stale activations.
            $activation->touch();

        } catch (Exception $ex) {
            Log::error(print_r($ex, true));
            return Helpers\UserActivationAttemptResult::UnknownError;
        }

        return Helpers\UserActivationAttemptResult::Success;
    }

    public function setisEnabledAttribute($value) {
        $this->isactive = $value;
        if ($value == '0') {
            $userTokens = $this->tokens;
            foreach ($userTokens as $token) {
                $token->revoke();
            }
        }
    }

    public function getisEnabledAttribute() {
        return $this->isactive;
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
        'bypassable_sites', 'blocked_triggers', 'blocked_applications', 'time_restrictions',
        "BypassesPermitted", "BypassDuration", "DisableBypass",
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];


    public function getBypassesPermittedAttribute() {
        return $this->getConfigValue("BypassesPermitted");
    }

    public function setBypassesPermittedAttribute($value) {
        $this->setConfigValue("BypassesPermitted", $value);
    }

    public function getBypassDurationAttribute() {
        return $this->getConfigValue("BypassDuration");
    }

    public function setBypassDurationAttribute($value) {
        $this->setConfigValue("BypassDuration", $value);
    }

    public function getDisableBypassAttribute() {
        return $this->getConfigValue("DisableBypass");
    }

    public function setDisableBypassAttribute($value) {
        $this->setConfigValue("DisableBypass", $value);
    }
}
