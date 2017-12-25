<?php

/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace App\Http\Controllers;

use App\Group;
use App\User;
use App\App;
use App\AppGroup;
use App\AppGroupToApp;
use App\UserGroupToAppGroup;
use App\GroupFilterAssignment;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Log;
class GroupController extends Controller
{
    
    public function __construct() {
        
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Group::with('assignedFilterIds')->get();
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
        $this->validate($request, [
            'name' => 'required'
        ]);
        
        $groupInput = $request->except(['assigned_filter_ids','assigned_app_groups']);
        $groupListAssigments = $request->only('assigned_filter_ids');
        $assignedAppgroups = $request->only('assigned_app_groups');
        
        $myGroup = Group::firstOrCreate($groupInput);

        if(!is_null($groupListAssigments) && array_key_exists('assigned_filter_ids', $groupListAssigments) && is_array($groupListAssigments['assigned_filter_ids']))
        {
            $createdAt = Carbon::now();
            $updatedAt = Carbon::now();
            $groupListAssignmentMassInsert = array();
            
            foreach($groupListAssigments['assigned_filter_ids'] as $groupList)
            {
                $groupList['group_id'] = $myGroup->id;
                $groupList['created_at'] = $createdAt;
                $groupList['updated_at'] = $updatedAt;
                array_push($groupListAssignmentMassInsert, $groupList);                
            }
            
            GroupFilterAssignment::insertIgnore($groupListAssignmentMassInsert);
        }
        
        if(is_array($assignedAppgroups['assigned_app_groups'])) {
            $arr_app_groups = array();
            foreach($assignedAppgroups['assigned_app_groups'] as $app_group_id) {
                $arr = array(
                    'user_group_id' => $id,
                    'app_group_id' => $app_group_id
                );
                array_push($arr_app_groups, $arr);
                //UserGroupToAppGroup::create($arr);
            }
            UserGroupToAppGroup::insert($arr_app_groups);
        }
        $myGroup->rebuildGroupData();
        
        return response('', 204);
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return Group::where('id', $id)->get();
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
            'name' => 'required'
        ]);
        
        $groupInput = $request->except(['assigned_filter_ids', 'assigned_app_groups']);
        $groupListAssigments = $request->only('assigned_filter_ids');
        $assignedAppgroups = $request->only('assigned_app_groups');

        Group::where('id', $id)->update($groupInput);
        
        GroupFilterAssignment::where('group_id', $id)->delete();
        
        if(!is_null($groupListAssigments) && array_key_exists('assigned_filter_ids', $groupListAssigments) && is_array($groupListAssigments['assigned_filter_ids']))
        {
            $createdAt = Carbon::now();
            $updatedAt = Carbon::now();
            $groupListAssignmentMassInsert = array();
            
            foreach($groupListAssigments['assigned_filter_ids'] as $groupList)
            {
                $groupList['group_id'] = $id;
                $groupList['created_at'] = $createdAt;
                $groupList['updated_at'] = $updatedAt;
                array_push($groupListAssignmentMassInsert, $groupList);                
            }
            
            GroupFilterAssignment::insertIgnore($groupListAssignmentMassInsert);
        }
        
        
        UserGroupToAppGroup::where('user_group_id', $id)->delete();
        if(is_array($assignedAppgroups['assigned_app_groups'])) {
            $arr_app_groups = array();
            foreach($assignedAppgroups['assigned_app_groups'] as $app_group_id) {
                $arr = array(
                    'user_group_id' => $id,
                    'app_group_id' => $app_group_id
                );
                array_push($arr_app_groups, $arr);
                //UserGroupToAppGroup::create($arr);
            }
            UserGroupToAppGroup::insert($arr_app_groups);
        }
        $thisGroup = Group::where('id', $id)->first();
        
        if(!is_null($thisGroup))
        {
            // Update timestamps.
            $thisGroup->touch();
            
            // Rebuild payload for this group.
            $thisGroup->rebuildGroupData();
        }
        
        return response('', 204);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $thisGroup = Group::where('id', $id)->first();
        if(!is_null($thisGroup))
        {
            // Ensure we orphan all users of this group properly.
            // For this, we need to set their group ID to -1. Otherwise,
            // the retained value will cause them to suddenly become a 
            // part of the next group created after this delete.
            User::where('group_id', $id)->update(['group_id' => -1]);

            GroupFilterAssignment::where('group_id', $id)->delete();
            UserGroupToAppGroup::where('user_group_id', $id)->delete();
            
            // Get any payload stuff off the file system.
            $thisGroup->destroyGroupData();
            
            // Finally, do away with this group.
            $thisGroup->delete();
        }
        
        return response('', 204);
    }

    public function get_app_data() {
        $apps = App::get();
        $app_groups = AppGroup::get();
        $group_to_apps = AppGroupToApp::get();
        return response()->json([
            'apps'=>$apps, 
            'app_groups'=>$app_groups,
            'group_to_apps'=>$group_to_apps
            ]);
    }

    public function get_app_data_with_groupid($group_id) {
        $apps = App::get();
        $app_groups = AppGroup::get();
        $group_to_apps = AppGroupToApp::get();
        $selected_app_groups = UserGroupToAppGroup::where('user_group_id', $group_id)->get();
        return response()->json([
            'apps'=>$apps, 
            'app_groups'=>$app_groups,
            'group_to_apps'=>$group_to_apps,
            'selected_app_groups'=>$selected_app_groups]);
    }
}
