<?php

/*
 * Copyright � 2017 Darren Wiebe
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace App\Http\Controllers;

use App\AppUserActivation;
use App\SystemPlatform;
use App\SystemVersion;
use App\Utils;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UpdateController extends Controller
{

    /**
     * Returns the update XML file.
     *
     * @return \Illuminate\Http\Response
     */
    public function retrieve(Request $request, $platform)
    {
        $platforms = Cache::remember('SystemPlatform_' . $platform, 3600, function() use ($platform) {
            return SystemPlatform::where('os_name', '=', $platform)->get();
        });

        $data = ["platform" => $platform, "activation_id" => $request->input("acid", "acid")];

        $osVersion = $request->input("os", "0");
        $appVersion = $request->input("v", "0");

        if ($osVersion == "0") {
            $activation = AppUserActivation::where('identifier', $data['activation_id'])->first();
            if ($activation != null) {
                if (!empty($activation->os_version)) {
                    $osVersion = $activation->os_version;
                }
                //TODO: remove, debug code
                if ($osVersion == "0") {
                    if (!empty($activation->config_override)) {
                        $config = json_decode($activation->config_override);
                        if (isset($config->UpdateChannel) && $config->UpdateChannel == "Alpha") {
                            $osVersion = "10";
                        }
                    }
                }
                //End of debug code
            }
        }

        if ($platforms->count() > 0) {
            $os = $platforms->first();
            $osVersionParts = explode(".", $osVersion);
            if ($os->platform == SystemPlatform::PLATFORM_WIN) {
                if (!empty($osVersionParts) && $osVersionParts[0] < 10) {
                    $platforms = collect(); //don't support windows less than 10. Using a collection so that it's an object and the rest of the system will work.
                    Log::info("Skipping update for " . $platform . " v " . $osVersion . ' Details: ' . json_encode($request->all()));
                }
            } else if ($os->platform == SystemPlatform::PLATFORM_OSX) {
                if (!empty($osVersionParts) && $osVersionParts[0] < 11) {
                    $platforms = collect(); //don't support OSX less than 11 (Big Sur). Using a collection so that it's an object and the rest of the system will work.
                    Log::info("Skipping update for " . $platform . " v " . $osVersion . ' Details: ' . json_encode($request->all()));
                }
            }
        }

        if ($platforms->count() > 0) {
            $os = $platforms->first();
            $data["os_name"] = $os->os_name;
            $platform_id = $os->id;
            $versions = Cache::remember('SystemVersion_isactive_' . $platform_id, 3600, function() use ($platform_id) {
                SystemVersion::where('platform_id', '=', $platform_id)->where('active', '=', 1)->get();
            });

            if ($versions->count() > 0) {
                $version = $versions->first();
                $data['app_name'] = $version->app_name;
                $data['file_name'] = $version->file_name;
                $data['file_ext'] = $version->file_ext;
                $data['version_number'] = $version->version_number;
                $data['changes'] = array($version->changes);

                $data['channels'] = [
                    [
                        'release' => 'Alpha',
                        'version_number' => $version->alpha,
                        'signature' => $version->alpha_ed_signature
                    ],
                    [
                        'release' => 'Beta',
                        'version_number' => $version->beta,
                        'signature' => $version->beta_ed_signature
                    ],
                    [
                        'release' => 'Stable',
                        'version_number' => $version->stable,
                        'signature' => $version->stable_ed_signature
                    ],
                ];
// This comment is to show the format we need it in.  Changing the format will break updates.
// It's been broken before and we may break it again so here's a reminder.
//                $data['date'] = 'Tue, 20 Feb 2018 12:39:00 MST';
                $data['date'] = Carbon::parse($version->release_date)->toRfc7231String();

            } else {
                $data['app_name'] = "unavailable";
                $data['file_name'] = "unavailable";
                $data['file_ext'] = "unavailable";
                $data['version_number'] = "---";
                $data['changes'] = array();
                $data['channels'] = [
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
                $data['date'] = "---";
            }
        } else {
            $data['app_name'] = "unavailable";
            $data['os_name'] = "unavailable";
            $data['file_name'] = "unavailable";
            $data['file_ext'] = "unavailable";
            $data['version_number'] = "---";
            $data['changes'] = array();
            $data['channels'] = [
                [
                    'release' => 'Alpha',
                    'version_number' => "---",
                    'signature' => "---",
                ],
                [
                    'release' => 'Beta',
                    'version_number' => "---",
                    'signature' => "---",
                ],
                [
                    'release' => 'Stable',
                    'version_number' => "---",
                    'signature' => "---",
                ],
            ];
            $data['date'] = "---";
        }

        return response()
            ->view('update.update_xml', $data)
            ->header('Content-Type', 'text/xml');
    }

    public function downloadRelease(Request $request, $activationId, $fileName)
    {
        if ($activationId == "acid") {
            $activationId = "";
        }
        Log::info("Update download from " . $request->ip() . " id: " . $activationId);
        AppUserActivation::setLastUpdateRequestTime($request->ip(), $activationId);
        $file = public_path() . "/releases/" . $fileName;
        return response()->download($file);
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
