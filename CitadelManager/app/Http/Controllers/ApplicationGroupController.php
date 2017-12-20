<?php

namespace App\Http\Controllers;
use App\AppGroup;
use App\Group;
use Illuminate\Http\Request;
use Log;
class ApplicationGroupController extends Controller
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
        //return AppGroup::with('user_group')->get();
        $app_groups = AppGroup::with('user_group')->get();
        foreach($app_groups as $app_group) {
            Log::debug($app_group);
            $arr_group_id = array();
            foreach($app_group->user_group as $group_item) {
                $arr_group_id[] = $group_item->user_group_id;
            }
            $user_group = Group::whereIn('id', $arr_group_id)->get();
            $str = '';
            foreach($user_group as $group_item) {
                if(strlen($str) > 0) {
                    $str .= ", ".$group_item->name;
                } else {
                    $str = $group_item->name;
                }
            }
            if (strlen($str) == 0) {
                $str = "-";
            }
            $app_group->user_group_name = $str;
        }
        return response()->json($app_groups);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        $this->validate($request, [
            'group_name' => 'required'
        ]);

        $input = $request->only(['group_name']);
        AppGroup::create($input); 
        return response('', 204);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        
        $applicationGroup = AppGroup::where('id', $id)->first();
        if (!is_null($applicationGroup)) {
            $applicationGroup->delete();
        }

        return response('', 204);
    }

    public function update(Request $request, $id) {
        
        // The javascript side/admin UI will not send
        // password or password_verify unless they are
        // intentionally trying to change a user's password.
    
        $input = $request->only(['group_name']);
        AppGroup::where('id', $id)->update($input);
     
        return response('', 204);
    }
}
