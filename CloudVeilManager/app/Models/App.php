<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class App extends Model
{
    use CrudTrait;
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'notes', 'platform_name'
    ];

    public function group()
    {
        return $this->belongsToMany('App\Models\AppGroup', 'app_group_to_apps', 'app_id', 'app_group_id');
    }
}
