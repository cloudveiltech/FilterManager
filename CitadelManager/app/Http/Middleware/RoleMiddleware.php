<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use App\User;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $roles
     * @return mixed
     */
    public function handle($request, Closure $next, $roles)
    {
        if (!Auth::check()) {
            return Redirect::to('login');
        }

        $roles = explode('|', $roles);

        foreach ($roles as $role) {
            if (Auth::user()->hasRole($role)) {
                return $next($request);
            }
        }

        return Response::make('Unauthorized action.', 403);
    }
}