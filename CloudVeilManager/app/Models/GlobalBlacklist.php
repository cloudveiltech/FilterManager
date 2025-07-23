<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalBlacklist extends Model
{
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'isactive', 'data_sha1',
    ];
}
