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
 * Description of Role
 *
 */
class Role extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'display_name', 'description'
    ];

    /**
     * Get the users that belong to the role.
     */
    public function users()
    {
        return $this->belongsToMany('App\User');
    }
}
