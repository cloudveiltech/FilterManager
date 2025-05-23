<?php

/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Description of FilterList
 *
 */
class FilterList extends Model
{

    public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'namespace', 'category', 'type', 'file_sha1', 'created_at', 'updated_at', 'entries_count'
    ];

    protected $attributes = [
        'entries_count' => 0
    ];

    /**
     * Gets the groups that have this filter list assigned.
     * @return type
     */
    public function group()
    {
        return $this->belongsToMany('App\Group');
    }
	
	public function getRelativeListPath()
	{
		return '/' . $this->namespace . '/' . $this->category . '/' . FilterRulesManager::TYPES[$this->type] . '.txt';
	}
}
