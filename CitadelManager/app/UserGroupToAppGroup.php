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
	
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'user_group_id' => 'int',
		'app_group_id' => 'int'
	];
	protected $fillable = [
		'user_group_id',
		'app_group_id'
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
