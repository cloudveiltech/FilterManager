<?php

namespace App\Http\Controllers\Admin\Traits;

use App\Models\AppGroupToApp;
use App\Models\Group;
use App\Models\UserGroupToAppGroup;

/**
 * Rebuilds the cached payload of every user group whose app lists were affected by a change
 * to an app or an app group. Group::rebuildGroupData() bakes the app names into the group's
 * zip and config_cache, so without this the clients keep serving stale app lists.
 */
trait RebuildsGroupData
{
    /**
     * User groups that include any of the given app groups.
     * Collect these before deleting pivot rows, otherwise there is nothing left to look up.
     */
    protected function userGroupIdsForAppGroups(array $appGroupIds): array
    {
        if (empty($appGroupIds)) {
            return [];
        }

        return UserGroupToAppGroup::whereIn('app_group_id', $appGroupIds)
            ->pluck('user_group_id')
            ->unique()
            ->all();
    }

    protected function appGroupIdsForApp($appId): array
    {
        return AppGroupToApp::where('app_id', $appId)->pluck('app_group_id')->unique()->all();
    }

    protected function rebuildGroups(array $userGroupIds): void
    {
        foreach (Group::whereIn('id', $userGroupIds)->get() as $group) {
            $group->rebuildGroupData();
        }
    }
}
