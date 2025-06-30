<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class SystemVersion extends Model
{
    use CrudTrait;
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'system_versions';
    protected $fillable = [
        'id', 'app_name', 'file_name', 'version_number', 'changes', 'alpha',
        'beta', 'stable', 'release_date', 'active', 'file_ext', 'created_at', 'updated_at',
        'alpha_ed_signature', 'beta_ed_signature', 'stable_ed_signature', 'platform_id'
    ];


    public function platform()
    {
        return $this->belongsTo('App\Models\SystemPlatform', "platform_id", "id");
    }
}
