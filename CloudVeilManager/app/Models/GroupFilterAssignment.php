<?php

/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Yadakhov\InsertOnDuplicateKey;

class GroupFilterAssignment extends Model
{
    // Enables mass insert, and insert-ignore, insert-update etc.
    use InsertOnDuplicateKey;

    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'group_id', 'filter_list_id', 'as_blacklist', 'as_whitelist', 'as_bypass',
    ];

}
