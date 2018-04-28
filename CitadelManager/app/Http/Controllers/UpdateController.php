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
use App\SystemVersion;
use App\SystemPlatform;

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
            //'version_name' => '1.6.21 Release',
            'version_number' => '1.6.21',
            'changes' =>
              [
                'Changes to ssl',
              ],
            'channels' =>
              [
                [
                  'release' => 'Alpha',
                  'version_number' => '1.6.24'
                ],
                [
                  'release' => 'Beta',
                  'version_number' => '1.6.24'
                ],
                [
                  'release' => 'Stable',
                  'version_number' => '1.6.21'
                ]
            ],
            'date' => 'Tue, 20 Feb 2018 12:39:00 MST'
          ]
        )
        ->header('Content-Type', 'text/xml');
    }
    /**
     * Returns the version with JSON.
     *
     * @return \Illuminate\Http\Response
     */
    public function currentVersions(Request $request, $platform) {
      $platforms = SystemPlatform::where('os_name', '=', $platform)->get();
      Log::debug("---platforms----");
      Log::debug($platforms);
      if($platforms->count() > 0) {
        $os = $platforms->first();
        $platform_id = $os->id;
        $versions = SystemVersion::where('platform_id','=', $platform_id)->where('active','=',1)->get();
        Log::debug("---Versions----");
        Log::debug($versions);
        if($versions->count() > 0) {
          $version = $versions->first();
          return response()->json([
            "current_version" => $version->version_number,
            "platform"=>$platform,
            "release_date" => $version->release_date,
            "error"=>"",
            "success"=>true
          ]);
        } else {
          return response()->json([
            "current_version" => "unavailable",
            "platform"=>$platform,
            "release_date" => "---",
            "error"=>"Platform[".$platform."] doesn't exist.",
            "success"=>false
          ]);
        }
      } else {
        return response()->json([
          "current_version" => "unavailable",
          "platform"=>$platform,
          "release_date" => "---",
          "error"=>"Platform[".$platform."] doesn't exist.",
          "success"=>false
        ]);
      }
    }
}
