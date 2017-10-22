<?php

namespace App\Http\Controllers;
use App\GlobalBlacklist;
use Illuminate\Http\Request;

class GlobalBlacklistController extends Controller
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
        return GlobalBlacklist::get();
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

        $input = $request->only(['name', 'isactive']);
        $blacklist = GlobalBlacklist::create($input); 
        return response('', 204);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        
        $blacklist = GlobalBlacklist::where('id', $id)->first();
        if (!is_null($blacklist)) {
            $blacklist->delete();
        }

        return response('', 204);
    }

    public function update(Request $request, $id) {
        
        // The javascript side/admin UI will not send
        // password or password_verify unless they are
        // intentionally trying to change a user's password.
    
        $input = $request->only(['name', 'isactive']);
        GlobalBlacklist::where('id', $id)->update($input);

        return response('', 204);
    }
}
