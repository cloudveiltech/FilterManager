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
 * Description of FilterList
 *
 */
class FilterList extends Model {

    public $timestamps = true;

    /**
    * The accessors to append to the model's array form.
    *
    * @var array
    */
    protected $appends = ['entries_count'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'namespace', 'category', 'type', 'created_at', 'updated_at'
    ];

    /**
     * Gets all text rule entries for this filter list.
     * @return type
     */
    public function textRulesCountRelation() {
        return $this->hasOne('App\TextFilteringRule')->selectRaw('filter_list_id, count(*) as count')->groupBy('filter_list_id');
    }
    
    /**
     * Gets the number of rule entries for this filter list.
     * @return type
     */
    public function getEntriesCountAttribute() {
        $sum = 0;
        $textRelation = $this->textRulesCountRelation()->first();
        
        if(!is_null($textRelation))
        {
            $sum = $textRelation->count;
        }
        else            
        {
            // For list like NLP and Visual models, we'll just
            // return a count of 1, since there is no line
            // count or rule count per-se.
            $sum = 1;
        }
        
        return $sum;
    }

    /**
     * Gets the groups that have this filter list assigned.
     * @return type
     */
    public function group() {
        return $this->belongsToMany('App\Group');
    }

}
