<?php

namespace App\Http\Controllers;
use App\GlobalBlacklist;
use App\GlobalWhitelist;
use App\Group;
use Illuminate\Http\Request;

class ApplyToGroupController extends Controller
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

    /**
     * Apply to group with Black/White list
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function applyToGroup(Request $request) {
        // Check input params
        $this->validate($request, [
            'type' => 'required',
            'id_list' => 'required'
        ]);
        
        $input = $request->only(['type', 'id_list']);
        $type = $input['type'];
        $id_arr = $input['id_list'];

        $apply_list = [];           // Black/Whitelist array
        
        if($type == "blacklist") {
            $apply_list = GlobalBlacklist::get();
        } else if($type == "whitelist") {
            $apply_list = GlobalWhitelist::get();
        }

        // get groups that selected from front-end
        $groups = Group::whereIn("id", $id_arr)->get();
        
        $real_apply_data = [];
        foreach ($apply_list as $item) {
            array_push($real_apply_data, $item['name']);
        }

        // Replace new value & Save 
        foreach($groups as $group)
        {
            $app_cfg_str = $group['app_cfg'];
            $app_cfg_json = json_decode($app_cfg_str);

            // Checking the type & group black/white list type
            if ($type == "blacklist") {
                if( isset($app_cfg_json->WhitelistedApplications) ) {
                    $app_cfg_json->BlacklistedApplications = $real_apply_data;
                    unset ($app_cfg_json->WhitelistedApplications);
                } else {
                    $app_cfg_json->BlacklistedApplications = array_unique(array_merge($app_cfg_json->BlacklistedApplications, $real_apply_data), SORT_REGULAR);
                }
            } else if ($type == "whitelist") {
                if( isset($app_cfg_json->BlacklistedApplications) ) {
                    $app_cfg_json->WhitelistedApplications = $real_apply_data;
                    unset ($app_cfg_json->BlacklistedApplications);
                } else {
                    $app_cfg_json->WhitelistedApplications = array_unique(array_merge($app_cfg_json->WhitelistedApplications, $real_apply_data), SORT_REGULAR);
                }
            }
            // Set with new value
            $app_cfg_str = json_encode($app_cfg_json);
            $group['app_cfg'] = $app_cfg_str;
            $group->save();
        }
        return $groups;
    }
}
