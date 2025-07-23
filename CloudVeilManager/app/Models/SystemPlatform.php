<?php

namespace App\Models;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class SystemPlatform extends Model
{
    use CrudTrait;
    const PLATFORM_WIN = "WIN";
    const PLATFORM_OSX = "OSX";
    const PLATFORM_LINUX = "LINUX";
    const PLATFORM_SUPPORTED = [self::PLATFORM_WIN, self::PLATFORM_OSX, self::PLATFORM_LINUX];
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
