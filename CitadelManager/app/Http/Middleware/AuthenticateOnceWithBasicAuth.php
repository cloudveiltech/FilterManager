<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Auth;

class AuthenticateOnceWithBasicAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, $next)
    {
        try {
            $ret = Auth::onceBasic();
        } catch(\Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException $e) {
            return response($e->message, 401);
        }

        // Of course, if unauthorized HTTP exception did not occur, run next middleware.
        return $next($request);
    }

}