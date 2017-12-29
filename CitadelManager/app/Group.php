<?php

/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\TextFilteringRule;
use App\NlpFilteringRule;
use App\ImageFilteringRule;
use App\AppGroup;
use App\App;
use App\UserGroupToAppGroup;
use App\Client\FilteringPlainTextListModel;
use App\Client\PlainTextFilteringListType;
use App\Client\NLPConfigurationModel;
use Log;

/**
 * Description of Group
 *
 */
class Group extends Model {

    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'isactive', 'app_cfg', 'data_sha1'
    ];

    /**
     * Gets all users assigned to this group.
     * @return type
     */
    public function users() {
        return $this->hasMany('App\User');
    }

    /**
     * Gets all filter lists assigned to this group.
     * @return type
     */
    public function assignedFilterIds() {
        // From the docs: 
        // https://laravel.com/docs/5.4/eloquent-relationships#has-many-through
        // The third argument is the name of the foreign key on the intermediate 
        // model, the fourth argument is the name of the foreign key on the 
        // final model, and the fifth argument is the local key.

        return $this->hasMany('App\GroupFilterAssignment');
    }

    public function getGroupDataPayloadPath(): string {
        $storageDir = storage_path();
        $groupDataZipFolder = $storageDir . DIRECTORY_SEPARATOR . 'group_data' . DIRECTORY_SEPARATOR . $this->id;
        if (!file_exists($groupDataZipFolder)) {
            mkdir($groupDataZipFolder, 0755, true);
        }

        $groupDataZipPath = $groupDataZipFolder . DIRECTORY_SEPARATOR . 'data.zip';

        return $groupDataZipPath;
    }

    public function rebuildGroupData() {

        $groupDataZipPath = $this->getGroupDataPayloadPath();

        $zip = new \ZipArchive();
        $zip->open($groupDataZipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        // Build the app config into an array. We'll serialize this after
        // and write it to the zip file last.
        $nlpEnabledCategories = array();
        $compiledAppConfiguration = array();
        $compiledAppConfiguration['ConfiguredLists'] = array();
        $compiledAppConfiguration['ConfiguredNlpModels'] = array();

        $groupLists = $this->assignedFilterIds()->get();

        foreach ($groupLists as $listIndex) {

            $list = FilterList::where('id', $listIndex->filter_list_id)->first();

            if (!is_null($list)) {

                $listNamespace = $list->namespace;
                $listCategory = $list->category;
                $listType = $list->type;
                $listId = $list->id;

                switch ($listType) {
                    case 'Filters': {
                            $inMemFilterFile = '';
                            $filters = TextFilteringRule::where('filter_list_id', '=', $listId)->get();

                            foreach ($filters as $filter) {
                                $inMemFilterFile .= $filter->rule . "\n";
                            }

                            $entryRelativePath = '/' . $listNamespace . '/' . $listCategory . '/rules.txt';

                            if ($listIndex->as_blacklist) {
                                array_push($compiledAppConfiguration['ConfiguredLists'], new FilteringPlainTextListModel(PlainTextFilteringListType::Blacklist, $entryRelativePath));
                            } else if ($listIndex->as_whitelist) {
                                array_push($compiledAppConfiguration['ConfiguredLists'], new FilteringPlainTextListModel(PlainTextFilteringListType::Whitelist, $entryRelativePath));
                            } else if ($listIndex->as_bypass) {
                                array_push($compiledAppConfiguration['ConfiguredLists'], new FilteringPlainTextListModel(PlainTextFilteringListType::BypassList, $entryRelativePath));
                            }

                            $zip->addFromString($entryRelativePath, $inMemFilterFile);
                        }
                        break;

                    case 'Triggers': {
                            $inMemFilterFile = '';
                            $filters = TextFilteringRule::where('filter_list_id', '=', $listId)->get();

                            foreach ($filters as $filter) {
                                $inMemFilterFile .= $filter->rule . "\n";
                            }

                            $entryRelativePath = '/' . $listNamespace . '/' . $listCategory . '/triggers.txt';

                            array_push($compiledAppConfiguration['ConfiguredLists'], new FilteringPlainTextListModel(PlainTextFilteringListType::TextTrigger, $entryRelativePath));

                            $zip->addFromString($entryRelativePath, $inMemFilterFile);
                        }
                        break;

                    case 'NLP': {

                            $entryRelativePath = '/' . $listNamespace . '/nlp/nlp.model';

                            if (!array_key_exists($entryRelativePath, $nlpEnabledCategories)) {

                                // Because of our weird setup here with NLP list entries, we
                                // have to get all entries for this namespace to accurately
                                // track down the BLOB entry for the actual NLP model that
                                // this selected category belongs to.
                                $allNlpListForNamespace = FilterList::where(['namespace' => $listNamespace, 'type' => 'NLP'])->get();

                                $filter = null;
                                foreach ($allNlpListForNamespace as $nlpListInNamespace) {
                                    if (is_null($filter)) {
                                        $filter = NlpFilteringRule::where('filter_list_id', '=', $nlpListInNamespace->id)->first();
                                    }
                                }

                                // We have not discovered this NLP model file yet, so add it to the zip
                                // and create a new array key using the ZIP relative path.
                                $zip->addFromString($entryRelativePath, $filter->data);

                                $nlpEnabledCategories[$entryRelativePath] = array();
                            }

                            // Add this enabled NLP category to the already-discovered NLP model.
                            // We'll sort through each model path (as the key) and create a valid
                            // NLPConfigurationModel instance for each with a list of enabled
                            // categories (array value) later.
                            array_push($nlpEnabledCategories[$entryRelativePath], $listCategory);
                        }
                        break;

                    case 'VISUAL': {

                            $filter = TextFilteringRule::where('filter_list_id', '=', $listId)->first();

                            $entryRelativePath = '/' . $listNamespace . '/visual/' . '/visual.vv';

                            $zip->addFromString($entryRelativePath, $filter->data);
                        }
                        break;
                }
            }
        }

        // Now process NLP model configurations, if any.
        foreach (array_keys($nlpEnabledCategories) as $nlpEnabledCategoryKey) {
            array_push($compiledAppConfiguration['ConfiguredNlpModels'], new NLPConfigurationModel($nlpEnabledCategoryKey, $nlpEnabledCategories[$nlpEnabledCategoryKey]));
        }

        // Merge app_groups into configuration.
        $app_cfg = json_decode($this->app_cfg, true);
        $app_group_ids = UserGroupToAppGroup::where('user_group_id',$this->id)->pluck('app_group_id');
        $app_groups = AppGroup::with('app')->find($app_group_ids);
        $apps = [];
        // The apps variable is populated like this to remove duplicates.
        foreach($app_groups AS $ag) {
            foreach ($ag['app'] AS $app) {
                $apps[ $app['name'] ] = $app['name'];
            }
        }
        $apps = array_values($apps);

        //We remove the Whitelist or Blacklist setting before saving to the .zip file as it's not needed.
        if (isset($app_cfg['Whitelist'])) {
            unset($app_cfg['Whitelist']);
            Log::info('Whitelisting apps');
            $app_cfg['WhitelistedApplications'] = $apps;
        } elseif (isset($app_cfg['Blacklist'])) {
            unset($app_cfg['Blacklist']);
            Log::info('Blacklisting apps');
            $app_cfg['BlacklistedApplications'] = $apps;
        }

        $compiledAppConfiguration = array_merge($compiledAppConfiguration, $app_cfg);
        $serializedFinalConfiguration = json_encode($compiledAppConfiguration);

        $zip->addFromString('cfg.json', $serializedFinalConfiguration);
        $zip->close();

        // Lastly, update this group's data hash.
        Group::where('id', $this->id)->update(['data_sha1' => sha1_file($groupDataZipPath)]);
    }

    public function destroyGroupData() {
        $groupDataZipPath = $this->getGroupDataPayloadPath();
        if (file_exists($groupDataZipPath)) {
            unlink($groupDataZipPath);
        }
    }

}
