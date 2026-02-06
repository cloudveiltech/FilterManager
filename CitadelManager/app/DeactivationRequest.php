<?php

/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Description of DeactivationRequest
 *
 */
class DeactivationRequest extends Model
{
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'identifier', 'device_id', 'granted',
    ];

    public function user()
    {
        return $this->;
    }

    public function activation()
    {
        return $this->belongsTo('App\AppUserActivation', 'identifier', 'identifier');
    }
}
