<?php

/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace App\Http\Controllers;

use App\Models\App;
use App\Models\AppGroup;
use App\Models\AppGroupToApp;
use App\Models\Group;
use App\Models\GroupFilterAssignment;
use App\Models\User;
use App\Models\UserGroupToAppGroup;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GroupController extends Controller
{

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $thisGroup = Group::where('id', $id)->first();
        if (!is_null($thisGroup)) {
            // Ensure we orphan all users of this group properly.
            // For this, we need to set their group ID to -1. Otherwise,
            // the retained value will cause them to suddenly become a
            // part of the next group created after this delete.
            User::where('group_id', $id)->update(['group_id' => -1]);

            GroupFilterAssignment::where('group_id', $id)->delete();
            UserGroupToAppGroup::where('user_group_id', $id)->delete();

            // Get any payload stuff off the file system.
            $thisGroup->destroyGroupData();
            // Finally, do away with this group.
            $thisGroup->delete();
        }

        return response('', 204);
    }

    public function get_app_data($type, $groupId)
    {
        $apps = App::get();
        $app_groups = AppGroup::get();
        $group_to_apps = AppGroupToApp::get();
        return response()->json([
            'apps' => $apps,
            'app_groups' => $app_groups,
            'group_to_apps' => $group_to_apps,
        ]);
    }

    public function get_app_data_with_groupid($groupId=null)
    {
        $apps = App::get();
        $app_groups = AppGroup::get();
        $group_to_apps = AppGroupToApp::get();
        if($groupId) {
            $selected_app_groups = UserGroupToAppGroup::where('user_group_id', $groupId)->get();
        } else {
            $selected_app_groups = [];
        }
        return response()->json([
            'apps' => $apps,
            'app_groups' => $app_groups,
            'group_to_apps' => $group_to_apps,
            'selected_app_groups' => $selected_app_groups
        ]);
    }
}
