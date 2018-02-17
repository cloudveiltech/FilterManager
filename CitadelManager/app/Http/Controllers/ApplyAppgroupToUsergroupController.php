<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Group;
use App\AppGroup;
use App\UserGroupToAppGroup;
class ApplyAppgroupToUsergroupController extends Controller
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
        return response('', 204);
    }

    public function getRetrieveData() {        
        $appGroups = AppGroup::get();
        $userGroups = Group::get();
        
        return response()->json(['user_groups' => $userGroups, 'app_groups' => $appGroups]);
    }

    public function getSelectedUsergroups($appgroup_id) {
        return UserGroupToAppGroup::where('app_group_id', $appgroup_id)->get();
    }
    /**
     * Apply to group with Black/White list
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function applyToGroup(Request $request) {
        // Check input params
        $this->validate($request, [
            'app_group_id' => 'required'
        ]);
        
        $input = $request->only(['app_group_id', 'user_group_ids']);
        $app_group_id = $input['app_group_id'];        
        $user_group_ids = $input['user_group_ids'];

        UserGroupToAppGroup::where('app_group_id', $app_group_id)->delete();
        if (is_array($user_group_ids)) {
            foreach($user_group_ids as $user_group_id) {
                UserGroupToAppGroup::Create([
                    'app_group_id' => $app_group_id,
                    'user_group_id' => $user_group_id
                ]);
            }
            foreach($user_group_ids as $user_group_id) {
                $user_group = Group::where('id', $user_group_id)->get()->first();
                $user_group->rebuildGroupData();
            }
        }
        return response('', 204);
    }
}
