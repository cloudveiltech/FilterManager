<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AppGroup extends Model
{
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'group_name'
    ];

    public function group_app(){
        return $this->hasMany('App\AppGroupToApp');
    }
    public function user_group() {
        return $this->hasMany('App\UserGroupToAppGroup');
    }
}
