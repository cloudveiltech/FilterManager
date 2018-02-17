<?php

namespace App\Http\Controllers;
use App\App;
use App\AppGroup;
use App\AppGroupToApp;
use Illuminate\Http\Request;
use App\Group;
use Log;
class ApplicationController extends Controller
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
        $applications = App::with('group')->get();
        foreach($applications as $app) {
            $arr_group_id = array();
            foreach($app->group as $group_item) {
                $arr_group_id[] = $group_item->app_group_id;
            }
            $app_group = AppGroup::whereIn('id', $arr_group_id)->get();
            $str = '';
            foreach($app_group as $group_item) {
                if(strlen($str) > 0) {
                    $str .= ", ".$group_item->group_name;
                } else {
                    $str = $group_item->group_name;
                }
            }
            if (strlen($str) == 0) {
                $str = "-";
            }
            $app->group_name = $str;
        }
        return response()->json($applications);
    }

    public function get_application() {
        return App::get();
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        $this->validate($request, [
            'name' => 'required'
        ]);

        $input = $request->only(['name', 'notes']);        
        $app = App::create($input);
        $assigned_groups = $request->only('assigned_appgroup');
        AppGroupToApp::where('app_id', $id)->delete();
        if(is_array($assigned_groups['assigned_appgroup'])) {
            $arr_assigned_groups = array();
            foreach($assigned_groups['assigned_appgroup'] as $group_id) {
                array_push($arr_assigned_groups, array(
                    'app_id' => $app->id,
                    'app_group_id'=> $group_id
                ));
            }
            AppGroupToApp::insert($arr_assigned_groups);
        }
        Log::debug($assigned_groups);
        return response('', 204);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        AppGroupToApp::where('app_id', $id)->delete();
        $application = App::where('id', $id)->first();
        if (!is_null($application)) {
            $application->delete();
        }

        return response('', 204);
    }

    public function update(Request $request, $id) {
        
        // The javascript side/admin UI will not send
        // password or password_verify unless they are
        // intentionally trying to change a user's password.
    
        $input = $request->only(['name', 'notes']);
        App::where('id', $id)->update($input);
        
        $assigned_groups = $request->only('assigned_appgroup');
        AppGroupToApp::where('app_id', $id)->delete();
        if(is_array($assigned_groups['assigned_appgroup'])) {
            $arr_assigned_groups = array();
            foreach($assigned_groups['assigned_appgroup'] as $group_id) {
                array_push($arr_assigned_groups, array(
                    'app_id' => $id,
                    'app_group_id'=> $group_id
                ));
            }
            Log::debug($arr_assigned_groups);
            AppGroupToApp::insert($arr_assigned_groups);
        }
        return response('', 204);
    }

    public function getApps() {
        $groups = Group::get();
        $arr = [];
        foreach ($groups as $group) {
            $app_cfg = json_decode($group->app_cfg);
            $apps_arr = [];
            if(isset ($app_cfg->WhitelistedApplications)){
                $apps_arr = $app_cfg->WhitelistedApplications;
            }
            if(isset ($app_cfg->BlacklistedApplications)){
                $apps_str = $app_cfg->BlacklistedApplications;
            }
            //$apps_arr = explode(",", $apps_string);
            //var_dump($apps_arr);
            foreach($apps_arr as $app) {
                if(array_search($app, $arr) === false) 
                {
                    $arr[] = $app;
                }
            }
        }
        asort($arr);
        foreach($arr as $key=>$value) {
            App::Create(['name'=>$value, 'notes'=>'']);
        }
        return response()->json($arr);
    }
    public function get_appgroup_data()
    {
        $app_groups = AppGroup::get();
        return response()->json([ 'app_groups'=>$app_groups]);
    }
    public function get_appgroup_data_with_app_id($id) {
        $app_groups = AppGroup::get();
        $selected_app_groups = AppGroupToApp::where('app_id', $id)->get();
        return response()->json([
            'app_groups'=>$app_groups,
            'selected_app_groups'=>$selected_app_groups]);
    }
}