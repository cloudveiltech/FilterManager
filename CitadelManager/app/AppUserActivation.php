<?php

namespace App;

use App\Console\Commands\ReportUpdateFailed;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppUserActivation extends Model
{
    use SoftDeletes;

    public static $WORKDAYS = ["monday", "tuesday", "wednesday", "thursday", "friday"];
    public static $ALL_DAYS = ["monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"];
    public static $DEFAULT = [
        "monday" => [
            "EnabledThrough" => ["00.00", "24.00"],
            "RestrictionsEnabled" => false
        ],
        "tuesday" => [
            "EnabledThrough" => ["00.00", "24.00"],
            "RestrictionsEnabled" => false
        ],
        "wednesday" => [
            "EnabledThrough" => ["00.00", "24.00"],
            "RestrictionsEnabled" => false
        ],
        "thursday" => [
            "EnabledThrough" => ["00.00", "24.00"],
            "RestrictionsEnabled" => false
        ],
        "friday" => [
            "EnabledThrough" => ["00.00", "24.00"],
            "RestrictionsEnabled" => false
        ],
        "saturday" => [
            "EnabledThrough" => ["00.00", "24.00"],
            "RestrictionsEnabled" => false
        ],
        "sunday" => [
            "EnabledThrough" => ["00.00", "24.00"],
            "RestrictionsEnabled" => false
        ],
    ];
    public static $TEMPLATES = [
        "workdays" => [
            "EnabledThrough" => ["00.00", "24.00"],
            "RestrictionsEnabled" => false
        ],
        "all" => [
            "EnabledThrough" => ["00.00", "24.00"],
            "RestrictionsEnabled" => false
        ],
    ];

    protected $fillable = [
        'identifier', 'device_id', 'user_id', 'ip_address', 'group_id',
        'bypass_quantity','bypass_period','bypass_used', 'debug_enabled',
        'check_in_days', 'alert_partner','config_override', 'last_update_requested_time',
        'last_sync_time', 'platform_name', 'friendly_name', 'app_version', 'os_version', 'banned'
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

    public static  function applyTemplates($timeRestrictions, $templates): array {
        if(empty($timeRestrictions)) {
            $timeRestrictions = self::$DEFAULT;
        }
        if(empty($templates)) {
            $templates = self::$TEMPLATES;
        }
        $timeRestrictions = self::applyTemplate($timeRestrictions, $templates["workdays"], self::$WORKDAYS);
        $timeRestrictions = self::applyTemplate($timeRestrictions, $templates["all"], self::$ALL_DAYS);
        return $timeRestrictions;
    }

    public static function applyTemplate($timeRestrictions, $template, $days): array {
        if($template["RestrictionsEnabled"]) {
            foreach($days as $day) {
                if(!$timeRestrictions[$day]["RestrictionsEnabled"]) {
                    $timeRestrictions[$day]["EnabledThrough"] = $template["EnabledThrough"];
                    $timeRestrictions[$day]["RestrictionsEnabled"] = true;
                }
            }
        }
        return $timeRestrictions;
    }
}
