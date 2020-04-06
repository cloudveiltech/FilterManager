<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SystemPlatform extends Model
{
    const PLATFORM_SUPPORTED = ["WIN", "OSX", "LINUX"];
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'system_platforms';
    protected $fillable = [
        'id', 'platform', 'os_name',
    ];
}
