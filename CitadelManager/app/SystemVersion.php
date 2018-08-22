<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SystemVersion extends Model
{
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'system_versions';
    protected $fillable = [
        'id', 'platform_id', 'app_name', 'file_name', 'version_number', 'changes', 'alpha',
        'beta', 'stable', 'release_date', 'active', 'file_ext', 'created_at', 'updated_at',
    ];
}
