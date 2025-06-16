<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppUserActivation extends Model
{
    use CrudTrait;
    use SoftDeletes;

    protected $fillable = [
        'identifier', 'device_id', 'user_id', 'ip_address', 'group_id',
        'bypass_quantity','bypass_period','bypass_used', 'debug_enabled',
        'check_in_days', 'alert_partner','config_override', 'last_update_requested_time',
        'last_sync_time', 'platform_name', 'friendly_name', 'app_version', 'os_version', 'banned'
    ];

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function deactivation_request()
    {
        return $this->hasMany('App\Models\DeactivationRequest', 'identifier', 'identifier');
    }

    public function group()
    {
        return $this->belongsTo('App\Models\Group');
    }

    public static function setLastUpdateRequestTime($requestIp, $activationId) {
        $query = null;
        if(!empty($activationId)) {
            $query = AppUserActivation::where('identifier', $activationId);
        } else {
            $query = AppUserActivation::where('ip_address', $requestIp);
        }
        $query->update(["last_update_requested_time" => Carbon::now()]);
    }


    function getBypassFormattedAttribute() {
        $result = "";
        if(isset($this->bypass_period)) {
            $result = $this->bypass_period . " min";
        }
        if(isset($this->bypass_quantity)) {
            if(!empty($result)) {
                $result .= ", ";
            }
            $result .= $this->bypass_quantity . " times/day";
        }

        return $result;
    }


    function getOsFormattedAttribute() {
        return $this->platform_name . " - " . $this->os_version;
    }
}
