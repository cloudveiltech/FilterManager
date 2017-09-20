<?php

/*
 * Copyright © 2017 Darren Wiebe
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace App\Http\Controllers;

use Validator;
use App\User;
use App\Group;
use App\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\AppUserActivation;
use Laravel\Passport\Passport;
use Log;
use Carbon\Carbon;

class UpdateController extends Controller {

    /**
     * Returns the update XML file.
     *
     * @return \Illuminate\Http\Response
     */
    public function retrieve(Request $request, $platform) {
      //$thisUser = \Auth::user();
      return response()
        ->view('update.windows.update_xml',
          [
            'platform' => $platform,
            'app_name' => 'CloudVeil',
            'file_name' => 'CloudVeil',
            'version_name' => '1.1.2 Release',
            'version_number' => '1.1.2',
            'changes' =>
              [
                'Fixed installer. Deleted installer solutions and bundled the projects into the main solution.

Fixed bad paths for nuget packages that caused tons of different package caches to be generated.

Upgraded to latest filter engine which includes IPV6 filtering support now. That seems to have fixed the expedia issue(s).

Bump to version 1.1.2.',
              ],
            'date' => 'Tue, 19 Sep 2017 13:15:00 MST'
          ]
        )
        ->header('Content-Type', 'text/xml');
    }

}
