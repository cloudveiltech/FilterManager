<?php

namespace App\Http\Controllers;
use App\GlobalWhitelist;
use Illuminate\Http\Request;

class GlobalWhitelistController extends Controller
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
        return GlobalWhitelist::get();
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
        $whitelist = GlobalWhitelist::create($input); 
        return response('', 204);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        
        $whitelist = GlobalWhitelist::where('id', $id)->first();
        if (!is_null($whitelist)) {
            $whitelist->delete();
        }

        return response('', 204);
    }

    public function update(Request $request, $id) {
        
        // The javascript side/admin UI will not send
        // password or password_verify unless they are
        // intentionally trying to change a user's password.
    
        $input = $request->only(['name', 'isactive']);
        GlobalWhitelist::where('id', $id)->update($input);

        return response('', 204);
    }
}
