<?php

/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace App\Http\Controllers;

use App\DeactivationRequest;
use Illuminate\Http\Request;
use App\Events\DeactivationRequestGranted;  
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
        $length = $request->input('length');
        $search = $request->input('search')['value'];
        $recordsTotal = DeactivationRequest::count();
        if(empty($search)) {
            $rows = DeactivationRequest::with(['user'])
                ->offset($start)
                ->limit($length)
                ->get();
            $recordsFilterTotal = $recordsTotal;
        } else {
            $rows = DeactivationRequest::with(['user'])
                ->where('identifier', 'like',"%$search%")
                ->offset($start)
                ->limit($length)
                ->get();
            $recordsFilterTotal = DeactivationRequest::where('identifier', 'like',"%$search%")->count();
        }
        
        return response()->json([
            "draw" => intval($draw),
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsFilterTotal,
            "data" => $rows
        ]);
    }

    public function updateField(Request $request) {
        $id = $request->input('id');
        $value = intval($request->input('value'));   //0 or 1

        $id_arr = explode("_", $id);
        if($id_arr[0] != "deactivatereq") {
            return response()->json([
                "success" => false
            ]);
        }
        $activation_id = intval($id_arr[2]);
        DeactivationRequest::where('id', $activation_id)->update(['granted'=>$value]);
       
        return response()->json([
            "success" => true
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
            'granted' => 'required'            
        ]);
        
        $input = $request->only(['granted']);
        $deactivateRequest = DeactivationRequest::where('id', $id)->first();
        $deactivateRequest->update($input);
        Log::info("Logging an object: " . print_r($input, true));
        // If this is a deactivate request that we are granting then we fire an event.
        if ($input['granted'] == 1) {
            try {
                event(new DeactivationRequestGranted($deactivateRequest));
            } catch(\Exception $e){
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
}
