<?php

/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Zizaco\Entrust\Traits\EntrustUserTrait;
use App\AppUserActivation;
use Illuminate\Http\Request;
use Validator;
use Laravel\Passport\HasApiTokens;
use Log;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class UserActivationAttemptResult {

    const Success = 1;
    const ActivationLimitExceeded = 2;
    const AccountDisabled = 3;
    const GroupDisabled = 4;
    const IndentifyingInformationMissing = 5;
    const UnknownError = 6;

}

class User extends Authenticatable {

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
    protected static function boot()
    {
        parent::boot();
     
        // Order by name ASC
        static::addGlobalScope('order', function (Builder $builder) {
            $builder->orderBy('name', 'asc');
        });
    }
     

    public function group() {
        return $this->belongsTo('App\Group');
    }

    public function roles() {
        return $this->belongsToMany('App\Role');
    }

    public function permissions() {
        return $this->hasMany('App\Permission');
    }

    public function activations() {
        return $this->hasMany('App\AppUserActivation');
    }

    /**
     * Gets a count of all activations for this user.
     * @return type
     */
    public function activationsCountRelation() {
        return $this->hasOne('App\AppUserActivation')
            ->selectRaw('user_id, count(*) as count')
            ->where('updated_at','>=', Carbon::now()->subDays(config('app.license_expiration'))->format('Y-m-d H:i:s'))
            ->groupBy('user_id');
    }

    /**
     * Gets the number of rule entries for this filter list.
     * @return type
     */
    public function getActivationsUsedAttribute(): int {

        $activationRelation = $this->activationsCountRelation()->first();
        Log::debug($activationRelation);
        if (!is_null($activationRelation)) {
            return $activationRelation->count;
        }

        return 0;
    }

    /**
     * Attempts to activate the user or retrieve an existing activation
     * for the user from the given request.
     * @param Request $request
     * @return \App\UserActivationAttemptResult
     */
    public function tryActivateUser(Request $request) {
        
        if ($this->isactive == false) {
            return UserActivationAttemptResult::AccountDisabled;
        }
        
        $userGroup = $this->group()->first();
        if (!is_null($userGroup)) {
            if ($userGroup->isactive == false) {
                return UserActivationAttemptResult::GroupDisabled;
            }
        }
        
        $validator = Validator::make($request->all(), [
            'identifier' => 'required',
            'device_id' => 'required'
        ]);

        if ($validator->fails()) {
            return UserActivationAttemptResult::IndentifyingInformationMissing;            
        }
        
        $userInfo = $request->only(['identifier', 'device_id']);
        $userInfo['user_id'] = $this->id;
        
        try
        {
            $activation = AppUserActivation::firstOrCreate($userInfo);
            Log::debug('Created New Activation');
            Log::debug($activation);
            $numActivations = $this->getActivationsUsedAttribute();
            
            // Only deny this user if their activation is brand new
            // and it pushed the number of activations over the allowed
            // maximum.
            if($numActivations > $this->activations_allowed && $activation->wasRecentlyCreated){
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
        'name', 'email', 'password','isactive','group_id','activations_allowed'
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
