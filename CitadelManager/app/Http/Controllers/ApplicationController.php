<?php

namespace App\Http\Controllers;

use Log;

use App\App;
use App\AppGroup;
use App\AppGroupToApp;
use App\Group;
use App\UserGroupToAppGroup;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function __construct()
    {

    }

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
        $order_name = $request->input('columns')[intval($order)]['data'] ? $request->input('columns')[intval($order)]['data'] : 'name';
        $order_str = $request->input('order')[0]['dir'] ? $request->input('order')[0]['dir'] : 'ASC';

        $recordsTotal = App::count();
        $query = App::with(['group'])
            ->select('apps.*')
            ->when($search, function ($query) use ($search) {
                return $query->where('name', 'like', "%$search%");
            })
            ->when(($order_name == 'bypass_quantity'), function ($query) use ($order_str, $order_name) {
                return $query->orderBy("bypass_used", $order_str)
                    ->orderBy("bypass_quantity", $order_str)
                    ->orderBy("bypass_period", $order_str);

            }, function ($query) use ($order_str, $order_name) {
                return $query->orderBy($order_name, $order_str);
            });

        $recordsFilterTotal = $query->count();

        $applications = $query->offset($start)
            ->limit($length)
            ->get();

        foreach ($applications as $app) {
            $arr_group_id = array();
            foreach ($app->group as $group_item) {
                $arr_group_id[] = $group_item->app_group_id;
            }
            $app_group = AppGroup::whereIn('id', $arr_group_id)->get();
            $str = '';
            foreach ($app_group as $group_item) {
                if (strlen($str) > 0) {
                    $str .= ", " . $group_item->group_name;
                } else {
                    $str = $group_item->group_name;
                }
            }
            if (strlen($str) == 0) {
                $str = "-";
            }
            $app->group_name = $str;
        }

        return response()->json([
            "draw" => intval($draw),
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsFilterTotal,
            "data" => $applications,
        ]);
    }


    public function autosuggest_app(Request $request)
    {
        $term = $request->input("term");
        $os = $request->input("os");
        if(empty($os)) {
            return App::where('name', 'like', "%$term%")->
            orderBy('name', 'asc')->pluck("name");
        } else {
            return App::where("platform_name", $os)->
            where('name', 'like', "%$term%")->
            orderBy('name', 'asc')->pluck("name");
        }
    }


    public function get_application()
    {
        return App::orderBy('name', 'asc')->get();
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
            'name' => 'required',
        ]);

        $input = $request->only(['name', 'notes', 'platform_name']);

        $app = App::create($input);

        $assigned_groups = $request->only('assigned_appgroup');

        if (array_key_exists('assigned_appgroup', $assigned_groups) && is_array($assigned_groups['assigned_appgroup'])) {
            $arr_assigned_groups = array();

            foreach ($assigned_groups['assigned_appgroup'] as $group_id) {
                array_push($arr_assigned_groups, array(
                    'app_id' => $app->id,
                    'app_group_id' => $group_id,
                ));
            }

            AppGroupToApp::insert($arr_assigned_groups);
        }
		
		$this->rebuildGroupDataByApp($app->id);
		
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
        AppGroupToApp::where('app_id', $id)->delete();

        $application = App::where('id', $id)->first();
        if (!is_null($application)) {
            $application->delete();
        }
		
		$this->rebuildGroupDataByApp($id);

        return response('', 204);
    }

    public function update(Request $request, $id)
    {

        // The javascript side/admin UI will not send
        // password or password_verify unless they are
        // intentionally trying to change a user's password.

        $input = $request->only(['name', 'notes', 'platform_name']);
        App::where('id', $id)->update($input);

        $assigned_groups = $request->only('assigned_appgroup');
        AppGroupToApp::where('app_id', $id)->delete();

        if (isset($assigned_groups['assigned_appgroup']) && is_array($assigned_groups['assigned_appgroup'])) {
            $arr_assigned_groups = array();
            foreach ($assigned_groups['assigned_appgroup'] as $group_id) {
                array_push($arr_assigned_groups, array(
                    'app_id' => $id,
                    'app_group_id' => $group_id,
                ));
            }

            AppGroupToApp::insert($arr_assigned_groups);
			
			$this->rebuildGroupDataByApp($id);
        }

        return response('', 204);
    }

    public function getApps()
    {
        $groups = Group::get();
        $arr = [];
        foreach ($groups as $group) {
            $app_cfg = json_decode($group->app_cfg);
            $apps_arr = [];
            if (isset($app_cfg->WhitelistedApplications)) {
                $apps_arr = $app_cfg->WhitelistedApplications;
            }
            if (isset($app_cfg->BlacklistedApplications)) {
                $apps_str = $app_cfg->BlacklistedApplications;
            }
            foreach ($apps_arr as $app) {
                if (array_search($app, $arr) === false) {
                    $arr[] = $app;
                }
            }
        }

        asort($arr);

        foreach ($arr as $key => $value) {
            App::Create(['name' => $value, 'notes' => '']);
        }

        return response()->json($arr);
    }

    public function get_appgroup_data()
    {
        $app_groups = AppGroup::get();
        return response()->json(['app_groups' => $app_groups]);
    }

    public function get_appgroup_data_with_app_id($id)
    {
        $app_groups = AppGroup::get();
        $selected_app_groups = AppGroupToApp::where('app_id', $id)->get();
        return response()->json([
            'app_groups' => $app_groups,
            'selected_app_groups' => $selected_app_groups,
        ]);
    }

    private function rebuildGroupDataByApp($app_id)
    {
        $appGroupLists = AppGroupToApp::where('app_id', '=', $app_id)->get();
        if ($appGroupLists->count() > 0) {
            $arr_group_id = [];
            foreach ($appGroupLists as $app_group) {
                $arr_group_id[] = $app_group->app_group_id;
            }

            $userGroupLists = UserGroupToAppGroup::whereIn('app_group_id', $arr_group_id)->get();
            if ($userGroupLists->count() > 0) {
                foreach ($userGroupLists as $user_group) {
                    Group::where('id', '=', $user_group->user_group_id)->first()->rebuildGroupData();
                }
            }
        }
    }
}
