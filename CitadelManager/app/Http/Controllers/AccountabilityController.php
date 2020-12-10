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
use App\Events\AccountabilityPartnerEvent;

class AccountabilityController extends Controller {

    /**
     * Parse Accountability Input
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request) {
        $thisUser = \Auth::user();
//        Log::debug($request);
        //Log::info($request->getContent());
        // This next section of code is required because some versions of the client forward incorrectly
        // formatted JSON.
        if (!$request->has('details')) {
           if (preg_match('/\{.*\}/', $request->getContent(), $regs)) {
               $details = json_decode($regs[0]);
           } else {
               $details = false;
           }
        } else {
           $details = $request->input('details');
        }
        if ($details) {
 //          Log::debug(json_encode($details));

           Log::info('Request URI:   ' . $details->request_uri);
           Log::info('Category Name: ' . $details->category_name_string);
           Log::info('Matching Rule: ' . $details->matching_rule);
           Log::error('Triggering Event');
           event(new AccountabilityPartnerEvent(
               $thisUser,
               'CV4W_SITE_BLOCKED',
               'CV4W_WEBHOOK',
               substr($details->request_uri,0,100),
               'Device: ' . $request->input('device_id') . ' Site Blocked: ' . substr($details->request_uri,0,100),
               2,
               10)
           );
        } 

    }

}
