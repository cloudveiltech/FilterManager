<?php

/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace App\Models;

use App\Casts\Json;
use App\Client\FilteringPlainTextListModel;
use App\Client\PlainTextFilteringListType;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

/**
 * Description of Group
 *
 */
class Group extends Model
{
    use CrudTrait;

    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'isactive', 'app_cfg', 'data_sha1', 'config_cache', 'notes', 'assigned_application_rules_type', "group_config"
    ];

    public function users()
    {
        return $this->hasMany('App\Models\User');
    }

    protected function casts(): array
    {
        return [
            'app_cfg' => Json::class,
        ];
    }

    public function assignedFilterIds()
    {
        return $this->hasMany('App\Models\GroupFilterAssignment');
    }

    public function assignedWhitelistFilters()
    {
        return $this->belongsToMany('App\Models\FilterList',
            'App\Models\GroupFilterAssignment',
            'group_id',
            'filter_list_id',
            "id",
            "id")
            ->withPivot("as_blacklist", "as_whitelist", "as_bypass")
            ->wherePivot("as_whitelist", "=", 1);
    }

    public function assignedBlacklistFilters()
    {
        return $this->belongsToMany('App\Models\FilterList',
            'App\Models\GroupFilterAssignment',
            'group_id',
            'filter_list_id',
            "id",
            "id")
            ->withPivot("as_blacklist", "as_whitelist", "as_bypass")
            ->wherePivot("as_blacklist", "=", 1);
    }


    public function assignedBypassFilters()
    {
        return $this->belongsToMany('App\Models\FilterList',
            'App\Models\GroupFilterAssignment',
            'group_id',
            'filter_list_id',
            "id",
            "id")
            ->withPivot("as_blacklist", "as_whitelist", "as_bypass")
            ->wherePivot("as_bypass", "=", 1);
    }

    public function assignedApplicationRules()
    {
        return $this->belongsToMany('App\Models\AppGroup',
            'App\Models\UserGroupToAppGroup',
            'user_group_id',
            'app_group_id',
            "id",
            "id")
            ->withPivot("filter_type")
            ->wherePivot("filter_type", "!=", UserGroupToAppGroup::FILTER_TYPE_BLOCK_APPS);
    }

    public function getAssignedApplicationRulesTypeAttribute()
    {
        $value = $this->assignedApplicationRules->first()->pivot->filter_type;
        return (int)$value;
    }

    public function assignedBlockedApplicationRules()
    {
        return $this->belongsToMany('App\Models\AppGroup',
            'App\Models\UserGroupToAppGroup',
            'user_group_id',
            'app_group_id',
            "id",
            "id")
            ->withPivot("filter_type")
            ->wherePivot("filter_type", "=", UserGroupToAppGroup::FILTER_TYPE_BLOCK_APPS);
    }

    public function setAssignedApplicationRulesTypeAttribute($value)
    {
        if ($value == UserGroupToAppGroup::FILTER_TYPE_BLACKLIST || $value == UserGroupToAppGroup::FILTER_TYPE_WHITELIST) {
            UserGroupToAppGroup::where("user_group_id", $this->id)
                ->where("filter_type", "!=", UserGroupToAppGroup::FILTER_TYPE_BLOCK_APPS)
                ->update(["filter_type" => $value]);
        }
        return $value;
    }

    public function getGroupDataPayloadPath(): string
    {
        $storageDir = storage_path();
        $groupDataZipFolder = $storageDir . DIRECTORY_SEPARATOR . 'group_data' . DIRECTORY_SEPARATOR . $this->id;
        if (!file_exists($groupDataZipFolder)) {
            mkdir($groupDataZipFolder, 0755, true);
        }

        $groupDataZipPath = $groupDataZipFolder . DIRECTORY_SEPARATOR . 'data.zip';

        return $groupDataZipPath;
    }

    public function getGroupConfigAttribute()
    {
        return [$this->app_cfg];
    }

    public function setGroupConfigAttribute($value)
    {
        $this->app_cfg = $value[0] ?? "";
    }

    public function setNotesAttribute($value)
    {
        $this->attributes['notes'] = is_null($value) ? '' : $value;
    }

    public function rebuildGroupData()
    {
        $filterRulesManager = new FilterRulesManager();
        $groupDataZipPath = $this->getGroupDataPayloadPath();
        Log::debug('Creating or overwriting zip file at location: ' . $groupDataZipPath);
        $zip = new \ZipArchive();
        $zip->open($groupDataZipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        // Build the app config into an array. We'll serialize this after
        // and write it to the zip file last.
        $compiledAppConfiguration = array();
        $compiledAppConfiguration['ConfiguredLists'] = array();

        $groupLists = $this->assignedFilterIds()->get();

        foreach ($groupLists as $listIndex) {
            $list = FilterList::where('id', $listIndex->filter_list_id)->first();

            if (!is_null($list)) {
                $listNamespace = $list->namespace;
                $listCategory = $list->category;
                $listType = $list->type;
                $listId = $list->id;

                $filterFile = $filterRulesManager->getRulesetPath($listNamespace, $listCategory, FilterRulesManager::TYPES[$listType]);
                $entryRelativePath = '/' . $listNamespace . '/' . $listCategory . '/' . FilterRulesManager::TYPES[$listType] . '.txt';

                if (file_exists($filterFile)) {
                    //Log::error('We should be including this file rather than exporting everything again: ' . $filterFile);
                    Log::debug('Adding File: ' . $filterFile);
                    $zip->addFile($filterFile, $entryRelativePath);
                } else {
                    Log::error('File Does Not Exist: ' . $filterFile);
                }
                switch ($listType) {
                    case 'Filters':
                        if ($listIndex->as_blacklist) {
                            array_push($compiledAppConfiguration['ConfiguredLists'], new FilteringPlainTextListModel(PlainTextFilteringListType::Blacklist, $entryRelativePath));
                        } else {
                            if ($listIndex->as_whitelist) {
                                array_push($compiledAppConfiguration['ConfiguredLists'], new FilteringPlainTextListModel(PlainTextFilteringListType::Whitelist, $entryRelativePath));
                            } else {
                                if ($listIndex->as_bypass) {
                                    array_push($compiledAppConfiguration['ConfiguredLists'], new FilteringPlainTextListModel(PlainTextFilteringListType::BypassList, $entryRelativePath));
                                }
                            }
                        }
                        break;
                    case 'Triggers':
                        array_push($compiledAppConfiguration['ConfiguredLists'], new FilteringPlainTextListModel(PlainTextFilteringListType::TextTrigger, $entryRelativePath));
                        break;
                }
            }
        }

        // Merge app_groups into configuration.
        $app_cfg = $this->app_cfg;
        $app_group_ids = UserGroupToAppGroup::where('user_group_id', $this->id)->pluck('filter_type', 'app_group_id')->toArray();
        $app_groups = AppGroup::with('apps')->find(array_keys($app_group_ids));
        $whitelistApps = [];
        $blocklistApps = [];
        $blacklistApps = [];
        // The apps variable is populated like this to remove duplicates.
        foreach ($app_groups as $ag) {
            $collection = &$whitelistApps;
            if ($app_group_ids[$ag->id] == UserGroupToAppGroup::FILTER_TYPE_BLACKLIST) {
                $collection = &$blacklistApps;
            } else if ($app_group_ids[$ag->id] == UserGroupToAppGroup::FILTER_TYPE_BLOCK_APPS) {
                $collection = &$blocklistApps;
            }
            foreach ($ag['apps'] as $app) {
                $collection[$app['name']] = ["name" => $app['name'], "os" => $app["platform_name"]];
            }
        }
        $whitelistApps = array_values($whitelistApps);
        $blocklistApps = array_values($blocklistApps);
        $blacklistApps = array_values($blacklistApps);

        //We remove the Whitelist or Blacklist setting before saving to the .zip file as it's not needed.
        if (isset($app_cfg['Whitelist'])) {
            unset($app_cfg['Whitelist']);
            Log::info('Whitelisting apps for group: ' . $this->id);
        } elseif (isset($app_cfg['Blacklist'])) {
            unset($app_cfg['Blacklist']);
            Log::info('Blacklisting apps for group: ' . $this->id);
        } elseif (isset($app_cfg['Blocklist'])) {
            unset($app_cfg['Blocklist']);
            Log::info('Blocked apps for group: ' . $this->id);
        }

        $app_cfg['BlacklistedApplications'] = $blacklistApps;
        $app_cfg['WhitelistedApplications'] = $whitelistApps;
        $app_cfg['BlockedApplications'] = $blocklistApps;

        $compiledAppConfiguration = array_merge($compiledAppConfiguration, $app_cfg);
        $serializedFinalConfiguration = json_encode($compiledAppConfiguration);

        $zip->addFromString('cfg.json', $serializedFinalConfiguration);
        $zip->close();

        // Lastly, update this group's data hash.
        Group::where('id', $this->id)->update(
            [
                'data_sha1' => sha1_file($groupDataZipPath),
                'config_cache' => $serializedFinalConfiguration,
            ]
        );

        $this->config_cache = $serializedFinalConfiguration;
        Log::info('Caching Config.  Done rebuilding group data for group ' . $this->id);
    }

    function destroyGroupData()
    {
        $groupDataZipPath = $this->getGroupDataPayloadPath();
        if (file_exists($groupDataZipPath)) {
            unlink($groupDataZipPath);
        }
    }

    function getPrimaryDnsAttribute()
    {
        return $this->getConcatenatedConfigField("PrimaryDns", "PrimaryDnsV6");
    }

    function getSecondaryDnsAttribute()
    {
        return $this->getConcatenatedConfigField("SecondaryDns", "SecondaryDnsV6");
    }

    function getBypassFormattedAttribute()
    {
        return $this->getConcatenatedConfigField("BypassDuration", "BypassesPermitted", " min", " times/day");
    }

    private function getConcatenatedConfigField($key1, $key2, $suffix1 = "", $suffix2 = "")
    {
        $appConfig = $this->app_cfg;
        $v1 = $appConfig[$key1] ?? "";
        $v2 = $appConfig[$key2] ?? "";
        if (!empty($v1)) {
            if (!empty($v2)) {
                return $v1 . $suffix1 . ", " . $v2 . $suffix2;
            } else {
                return $v1 . $suffix1;
            }
        } else {
            return $v2 . $suffix2;
        }
    }
}
