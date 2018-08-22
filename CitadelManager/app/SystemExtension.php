<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SystemExtension extends Model
{
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'file_extensions';
    protected $fillable = [
        'sys_name', 'file_extensions',
    ];
}
