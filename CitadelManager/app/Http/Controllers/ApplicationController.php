<?php

namespace App\Http\Controllers;
use App\App;
use App\AppGroup;
use Illuminate\Http\Request;
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

        $input = $request->only(['name']);
        App::create($input); 
        return response('', 204);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        
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
    
        $input = $request->only(['name']);
        App::where('id', $id)->update($input);
     
        return response('', 204);
    }
}
