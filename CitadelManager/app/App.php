<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class App extends Model
{
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'notes'
    ];

    public function group() {
        return $this->hasMany('App\AppGroupToApp');
    }
}
