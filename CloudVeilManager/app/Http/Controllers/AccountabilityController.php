<?php

/*
 * Copyright © 2018 CloudVeil Technology
 */

namespace App\Http\Controllers;

use App\Events\AccountabilityPartnerEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AccountabilityController extends Controller {

    /**
     * Parse Accountability Input
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request) {
        $thisUser = \Auth::user();
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
           Log::info('Triggering Event');
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
