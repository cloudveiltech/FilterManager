<?php

/**
 * Created by LuisAnonio Model.
 * Date: Sun, 03 Dec 2017 17:45:50 +0000.
 */

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Class GroupContact
 *
 * @property int $group_id
 * @property int $contact_id
 *
 * @property \App\AppGroup $app_group
 * @property \App\App $app
 *
 * @package App
 */
class UserGroupToAppGroup extends Model
{
    const FILTER_TYPE_WHITELIST = 0;
    const FILTER_TYPE_BLACKLIST = 1;
    const FILTER_TYPE_BLOCK_APPS = 2;

    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'user_group_id' => 'int',
        'app_group_id' => 'int',
        'filter_type' => 'int'
    ];
    protected $fillable = [
        'user_group_id',
        'app_group_id',
        'filter_type'
    ];
    public function app_group()
    {
        return $this->belongsTo(\App\AppGroup::class);
    }

    public function user_group()
    {
        return $this->belongsTo(\App\Group::class);
    }
}
