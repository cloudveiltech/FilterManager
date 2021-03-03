<?php

namespace App;

use App\Console\Commands\ReportUpdateFailed;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class AppUserActivation extends Model
{

    protected $fillable = [
        'identifier', 'device_id', 'user_id', 'ip_address', 'group_id',
        'bypass_quantity','bypass_period','bypass_used', 'report_level',
        'check_in_days', 'alert_partner','config_override', 'last_update_requested_time',
        'last_sync_time', 'platform_name'
    ];

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function deactivation_request()
    {
        return $this->hasMany('App\DeactivationRequest', 'identifier', 'identifier');
    }

    public function group()
    {
        return $this->belongsTo('App\Group');
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
}
