<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use Log;
class checkDBLive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            DB::connection()->getPdo();
            if(DB::connection()->getDatabaseName()){
                Log::debug("Connection Success!" . DB::connection()->getDatabaseName());
                return $next($request);
            } else {
                Log::debug("Connection Failed1!");
                return response('System Failure.', 500);    
            }
        } catch (\Exception $e) {
            Log::debug("Connection Failed!");
            return response('System Failure.', 500);
        }   
    }
}
