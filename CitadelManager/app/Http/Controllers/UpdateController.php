<?php

/*
 * Copyright � 2017 Darren Wiebe
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
      return response()
        ->view('update.windows.update_xml',
          [
            'platform' => $platform,
            'app_name' => 'CloudVeil',
            'file_name' => 'CloudVeil',
            //'version_name' => '1.6.14 Release',
            'version_number' => '1.6.14',
            'changes' =>
              [
                'Changes to ssl',
              ],
            'channels' =>
              [
                [
                  'release' => 'Alpha',
                  'version_number' => '1.6.14'
                ],
                [
                  'release' => 'Beta',
                  'version_number' => '1.6.14'
                ],
                [
                  'release' => 'Stable',
                  'version_number' => '1.6.14'
                ]
            ],
            'date' => 'Wed, 13 Dec 2017 10:16:00 MST'
          ]
        )
        ->header('Content-Type', 'text/xml');
    }

}
