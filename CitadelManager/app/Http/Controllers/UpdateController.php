<?php

/*
 * Copyright � 2017 Darren Wiebe
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace App\Http\Controllers;

use App\SystemPlatform;
use App\SystemVersion;
use Illuminate\Http\Request;

class UpdateController extends Controller
{

    /**
     * Returns the update XML file.
     *
     * @return \Illuminate\Http\Response
     */
    public function retrieve(Request $request, $platform)
    {
        $platforms = SystemPlatform::where('os_name', '=', $platform)->get();
        $arr_data = ["platform" => $platform];

        if ($platforms->count() > 0) {
            $os = $platforms->first();
            $platform_id = $os->id;
            $versions = SystemVersion::where('platform_id', '=', $platform_id)->where('active', '=', 1)->get();
            if ($versions->count() > 0) {
                $version = $versions->first();
                $arr_data['app_name'] = $version->app_name;
                $arr_data['file_name'] = $version->file_name;
                $arr_data['version_number'] = $version->version_number;
                $arr_data['changes'] = array($version->changes);
                $arr_data['channels'] = [
                    [
                        'release' => 'Alpha',
                        'version_number' => $version->alpha,
                    ],
                    [
                        'release' => 'Beta',
                        'version_number' => $version->beta,
                    ],
                    [
                        'release' => 'Stable',
                        'version_number' => $version->stable,
                    ],
                ];
                $arr_data['date'] = $version->release_date;
            } else {
                $arr_data['app_name'] = "unavailable";
                $arr_data['file_name'] = "unavailable";
                $arr_data['version_number'] = "---";
                $arr_data['changes'] = array();
                $arr_data['channels'] = [
                    [
                        'release' => 'Alpha',
                        'version_number' => "---",
                    ],
                    [
                        'release' => 'Beta',
                        'version_number' => "---",
                    ],
                    [
                        'release' => 'Stable',
                        'version_number' => "---",
                    ],
                ];
                $arr_data['date'] = "---";
            }
        } else {
            $arr_data['app_name'] = "unavailable";
            $arr_data['file_name'] = "unavailable";
            $arr_data['version_number'] = "---";
            $arr_data['changes'] = array();
            $arr_data['channels'] = [
                [
                    'release' => 'Alpha',
                    'version_number' => "---",
                ],
                [
                    'release' => 'Beta',
                    'version_number' => "---",
                ],
                [
                    'release' => 'Stable',
                    'version_number' => "---",
                ],
            ];
            $arr_data['date'] = "---";
        }

        return response()
            ->view('update.windows.update_xml', $arr_data)
            ->header('Content-Type', 'text/xml');
    }
    /**
     * Returns the version with JSON.
     *
     * @return \Illuminate\Http\Response
     */
    public function currentVersions(Request $request, $platform)
    {
        $platforms = SystemPlatform::where('os_name', '=', $platform)->get();
        if ($platforms->count() > 0) {
            $os = $platforms->first();
            $platform_id = $os->id;
            $versions = SystemVersion::where('platform_id', '=', $platform_id)->where('active', '=', 1)->get();
            if ($versions->count() > 0) {
                $version = $versions->first();
                return response()->json([
                    "current_version" => $version->version_number,
                    "platform" => $platform,
                    "release_date" => $version->release_date,
                    "error" => "",
                    "success" => true,
                ]);
            } else {
                return response()->json([
                    "current_version" => "unavailable",
                    "platform" => $platform,
                    "release_date" => "---",
                    "error" => "Platform[" . $platform . "] doesn't exist.",
                    "success" => false,
                ]);
            }
        } else {
            return response()->json([
                "current_version" => "unavailable",
                "platform" => $platform,
                "release_date" => "---",
                "error" => "Platform[" . $platform . "] doesn't exist.",
                "success" => false,
            ]);
        }
    }
}
