<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class AppGroup extends Model
{
    use CrudTrait;
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'group_name',
    ];

    public function apps()
    {
        return $this->belongsToMany('App\Models\App', 'app_group_to_apps', 'app_group_id', 'app_id');
    }
}
