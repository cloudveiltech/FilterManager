<?php

/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use App\Models\AppUserActivation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Laravel\Passport\HasApiTokens;
use Zizaco\Entrust\Traits\EntrustUserTrait;

class UserActivationAttemptResult {
    const Success = 1;
    const ActivationLimitExceeded = 2;
    const AccountDisabled = 3;
    const GroupDisabled = 4;
    const IndentifyingInformationMissing = 5;
    const UnknownError = 6;

}

class User extends Authenticatable {
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

    /**
     * Set the default orderBy
     */
    protected static function boot() {
        parent::boot();

        // Order by name ASC
        static::addGlobalScope('order', function (Builder $builder) {
            $builder->orderBy('name', 'asc');
        });
    }

    public function group() {
        return $this->belongsTo('App\Models\Group');
    }

    public function roles() {
        return $this->belongsToMany('App\Models\Role');
    }

    public function permissions() {
        return $this->hasMany('App\Models\Permission');
    }

    public function activations() {
        return $this->hasMany('App\Models\AppUserActivation');
    }

    public function findActivationById($identifier) {
        foreach($this->activations as $activation) {
            if($activation->identifier == $identifier) {
                return $activation;
            }
        }
        return null;
    }

    /**
     * Gets a count of all activations for this user.
     */
    public function activationsCountRelation() {
        return $this->hasOne('App\Models\AppUserActivation')
            ->selectRaw('user_id, count(*) as count')
            ->where('updated_at', '>=', Carbon::now()->subDays(config('app.license_expiration'))->format('Y-m-d H:i:s'))
            ->groupBy('user_id');
    }

    /**
     * Gets the number of rule entries for this filter list.
     */
    public function getActivationsUsedAttribute(): int {

        $activationRelation = $this->activationsCountRelation()->first();
        //Log::debug($activationRelation);
        if (!is_null($activationRelation)) {
            return $activationRelation->count;
        }

        return 0;
    }


    public function tryActivateUser(Request $request) {
        return $this->tryActivateUserArray($request->all());
    }
    /**
     * Attempts to activate the user or retrieve an existing activation
     * for the user from the given request.
     * @param Request $request
     * @return \App\Models\UserActivationAttemptResult
     */
    public function tryActivateUserArray($params) {
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
        'name', 'email', 'password', 'isactive', 'group_id', 'activations_allowed', 'customer_id', 'config_override', 'relaxed_policy_passcode', 'enable_relaxed_policy_passcode'
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
