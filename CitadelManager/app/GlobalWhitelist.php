<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GlobalWhitelist extends Model
{
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'isactive', 'data_sha1'
    ];
}
