<?php

namespace App\Http\Middleware;

use App\Models\AppUserActivation;
use App\Models\SystemVersion;
use Closure;
use Illuminate\Support\Facades\Cache;

class CheckAndUpdateDeviceId
{
    const CACHE_TIMEOUT_MINUTES = 5;
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->has('identifier')) {
            $input = $request->input();

            $args = [$input['identifier']];
            $activationId = $input['identifier'];
            $whereStatement = "identifier = ?";

            if (!empty($input['device_id'])) {
                $whereStatement .= " and device_id = ?";
                $args[] = $input['device_id'];
            }

            $osVersion = "";
            if (!empty($input["os_version"])) {
                $osVersion = $input["os_version"];
            }

            $appVersion = "";
            if (!empty($input["app_version"])) {
                $appVersion = $input["app_version"];
            }

            $activation = Cache::remember('AppUserActivation ' . $activationId, self::CACHE_TIMEOUT_MINUTES*60, function() use ($whereStatement, $args) {
                return AppUserActivation::whereRaw($whereStatement, $args)->first();
            });

            if (!$activation && $request->has('identifier_2') && $request->has('device_id_2')) {//identifier_2 is passed in case we changed device name locally
                $args = [0 => $input['identifier_2'], 1 => $input['device_id_2']];
                $activationId = $input['identifier_2'];
                $activation = Cache::remember('AppUserActivation' . $activationId, self::CACHE_TIMEOUT_MINUTES*60, function() use ($whereStatement, $args) {
                    return AppUserActivation::whereRaw($whereStatement, $args)->first();
                });
                if ($activation) {
                    //update info
                    $activation->identifier = $input['identifier'];
                    $activation->device_id = $input['device_id'];
                }
            }
            if ($activation) {
                if ($appVersion != "") {
                    $activation->app_version = $appVersion;
                }
                if ($osVersion != "") {
                    $activation->os_version = $osVersion;
                }
                $activation->save();
                if($activation->banned == 1) {
                    return response("Device is blocked by server", 406);
                }
            }
        }
        return $next($request);
    }
}
