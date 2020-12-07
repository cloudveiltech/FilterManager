<?php

namespace App\Http\Middleware;

use App\AppUserActivation;
use App\SystemPlatform;
use Carbon\Carbon;
use Closure;

class CheckAndUpdateDeviceId
{
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

            // Get Specific Activation with $identifier
            $activation = AppUserActivation::whereRaw($whereStatement, $args)->first();
            if (!$activation && $request->has('identifier_2') && $request->has('device_id_2')) {//identifier_2 is passed in case we changed device name locally
                $args = [0=>$input['identifier_2'], 1=>$input['device_id_2']];
                $activation = AppUserActivation::whereRaw($whereStatement, $args)->first();
                if ($activation) {
                    //update info
                    $activation->identifier = $input['identifier'];
                    $activation->device_id = $input['device_id'];
                    $activation->save();
                }
            }
        }
        return $next($request);
    }
}
