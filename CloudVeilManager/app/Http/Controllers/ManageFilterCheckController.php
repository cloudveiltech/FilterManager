<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterCheckRequest;
use App\Services\FilterCheck\FilterChecker;
use App\Services\FilterCheck\FilterTargetResolver;
use Illuminate\Http\JsonResponse;

class ManageFilterCheckController extends Controller
{
    /**
     * Check whether URLs would be blocked for an activation, a user, a group, or
     * the default group, and which lists are responsible.
     */
    public function check(FilterCheckRequest $request, FilterTargetResolver $resolver, FilterChecker $checker): JsonResponse
    {
        $target = $resolver->resolve($request->validated());

        return response()->json($checker->check(
            $target,
            $request->urls(),
            $request->boolean('bypass_active'),
        ));
    }
}
