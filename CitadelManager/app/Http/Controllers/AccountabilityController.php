<?php

/*
 * Copyright © 2018 CloudVeil Technology
 */

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Validator;
use Laravel\Passport\Passport;
use Log;
use Carbon\Carbon;

use App\User;
use App\Group;
use App\Role;

class AccountabilityController extends Controller {

    /**
     * Parse Accountability Input
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request) {
        $thisUser = \Auth::user();
	Log::info($request);
    }

}
