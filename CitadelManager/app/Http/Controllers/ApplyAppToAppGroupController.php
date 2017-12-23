<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\App;
use App\AppGroup;
use App\AppGroupToApp;
class ApplyAppToAppGroupController extends Controller
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
        $apps = App::get();
        $appGroups = AppGroup::get();
        return response()->json(['apps' => $apps, 'app_groups' => $appGroups]);
    }

    public function getSelectedGroups($app) {
        return AppGroupToApp::where('app_id', $app)->get();
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
            'app_id' => 'required'
        ]);
        
        $input = $request->only(['app_id', 'group_ids']);
        $app_id = $input['app_id'];        
        $group_ids = $input['group_ids'];

        AppGroupToApp::where('app_id', $app_id)->delete();
        if (is_array($group_ids)) {
            foreach($group_ids as $group_id) {
                AppGroupToApp::Create([
                    'app_group_id' => $group_id,
                    'app_id' => $app_id
                ]);
            }
        }
        return response('', 204);
    }
}
