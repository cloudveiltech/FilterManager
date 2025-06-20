<?php

/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Description of FilterList
 *
 */
class FilterList extends Model
{
    use CrudTrait;

    public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'namespace', 'category', 'type', 'file_sha1', 'created_at', 'updated_at', 'entries_count'
    ];

    protected $appends = array('label');

    protected $attributes = [
        'entries_count' => 0
    ];

    /**
     * Gets the groups that have this filter list assigned.
     */
    public function group()
    {
        return $this->belongsToMany('App\Models\Group');
    }

    public function getLabelAttribute() {
        return $this->namespace . "/" . $this->category . "(" . $this->type . ")";
    }
}
