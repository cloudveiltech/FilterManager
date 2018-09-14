<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AppUserActivation extends Model
{

    protected $fillable = [
        'identifier', 'device_id', 'user_id', 'ip_address',
        'bypass_quantity','bypass_period','bypass_used', 'report_level',
        'check_in_days', 'alert_partner','config_override'
    ];

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function deactivation_request()
    {
        return $this->hasMany('App\DeactivationRequest', 'identifier', 'identifier');
    }
}
