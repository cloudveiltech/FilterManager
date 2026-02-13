<?php

namespace App\Http\Middleware;

use App\AppUserActivation;
use App\SystemPlatform;
use Carbon\Carbon;
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

            $activation = AppUserActivation::withTrashed()->whereRaw($whereStatement, $args)->first();
            if (!$activation && $request->has('identifier_2') && $request->has('device_id_2')) {//identifier_2 is passed in case we changed device name locally
                $args = [0 => $input['identifier_2'], 1 => $input['device_id_2']];
                $activation = AppUserActivation::withTrashed()->whereRaw($whereStatement, $args)->first();
                if ($activation) {
                    //update info
                    $activation->identifier = $input['identifier'];
                    $activation->device_id = $input['device_id'];
                }
            }

            if ($activation) {
                if($activation->trashed()) {
                    $activation->restore();
                }
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
