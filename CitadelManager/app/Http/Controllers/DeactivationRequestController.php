<?php

/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace App\Http\Controllers;

use App\AppUserActivation;
use App\DeactivationRequest;
use App\Events\DeactivationRequestGranted;
use Illuminate\Http\Request;
use Log;

class DeactivationRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $draw = $request->input('draw');
        $start = $request->input('start');
        $length = $request->input('length') ? $request->input('length') : 10;
        $search = $request->input('search')['value'];

        $order = $request->input('order')[0]['column'];
        $order_name = $request->input('columns')[intval($order)]['data'];
        $order_str = $request->input('order')[0]['dir'];

        $recordsTotal = DeactivationRequest::count();

        $query = DeactivationRequest::with(['user'])
            ->select('deactivation_requests.*')
            ->when($search, function ($query) use ($search) {
                return $query->leftJoin('users', 'deactivation_requests.user_id', '=', 'users.id')
                    ->where('deactivation_requests.device_id', 'like', "%$search%")
                    ->orWhere('users.email', 'like', "%$search%");
            })
            ->when(($order_name == "user.name" || $order_name == "user.email"), function ($query) use ($order_str, $order_name) {

                return $query->leftJoin('users', 'deactivation_requests.user_id', '=', 'users.id')
                    ->orderBy(str_replace("user", "users", $order_name), $order_str);

            }, function ($query) use ($order_str, $order_name) {
                return $query->orderBy($order_name, $order_str);
            });

        $recordsFilterTotal = $query->count();

        $rows = $query->offset($start)
            ->limit($length)
            ->get();

        return response()->json([
            "draw" => intval($draw),
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsFilterTotal,
            "data" => $rows,
        ]);
    }

    public function updateField(Request $request)
    {
        $id = $request->input('id');
        $value = intval($request->input('value')); //0 or 1

        $id_arr = explode("_", $id);
        if ($id_arr[0] != "deactivatereq") {
            return response()->json([
                "success" => false,
            ]);
        }
        $activation_id = intval($id_arr[2]);
        $deactivateRequest = DeactivationRequest::where('id', $activation_id)->first();
        $deactivateRequest->update(['granted' => $value]);

        if($value == 1) {
            try {
                event(new DeactivationRequestGranted($deactivateRequest));
            } catch (\Exception $e) {
                Log::error($e);
            }
        }

        return response()->json([
            "success" => true,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // No forms here kids.
        return response('', 405);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Only the app can request/create these, and they
        // are controlled separately.
        return response('', 405);
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // No forms here kids.
        return response('', 405);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        // No forms here kids.
        return response('', 405);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'granted' => 'required',
        ]);

        $input = $request->only(['granted']);
        $deactivateRequest = DeactivationRequest::where('id', $id)->first();
        $deactivateRequest->update($input);
        // If this is a deactivate request that we are granting then we fire an event.
        if ($input['granted'] == 1) {
            try {
                event(new DeactivationRequestGranted($deactivateRequest));
            } catch (\Exception $e) {
                Log::error($e);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        DeactivationRequest::destroy($id);
        return response('', 204);
    }

    public function apiCreateDeactivationRequest(Request $request) {
        $request->validate([
            'identifier' => 'required',
        ]);

        $activation = AppUserActivation::where('identifier', $request->identifier)
            ->first();

        if (!$activation) {
            return response()->json([
                'success' => false,
                'message' => 'Activation not found',
            ]);
        }

        $deactivateRequest = DeactivationRequest::firstOrCreate([
            'identifier' => $request->identifier,
            'device_id' => $activation->device_id,
            'user_id' => $activation->user_id,
        ]);


        if ($request->has('approved') && $request->approved == true) {
            $deactivateRequest->update(['granted' => $request->approved]);
        }

        return response()->json([
            'success' => true,
        ]);
    }

}
