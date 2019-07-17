<?php

/*
 * Copyright © 2018 CloudVeil Technology, Inc.
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace App;

use Illuminate\Support\Facades\DB;

class FilterRulesManager 
{
        public function getRuleDataPath(): string
        {
            $storageDir = storage_path();
            $rulesZipPath = $storageDir . DIRECTORY_SEPARATOR . 'global-rules.zip';
            return $rulesZipPath;
        }
        
        public function getFilename($listNamespace, $listCategory, $filename, $separatorChar = '.') {
            return "$separatorChar$listNamespace$separatorChar$listCategory$separatorChar$filename";
        }
    
        public function getEtag($path) {
            return hash_file('sha1', $path);
        }

        public function getRulesetPath($namespace, $category, $type) {
            $filename = $this->getFilename($namespace, $category, "$type.txt");

            $storageDir = storage_path();
            return $storageDir . DIRECTORY_SEPARATOR . $filename;
        }

        // Helps reduce memory usage for rule file building.
        public function buildFile($filename, $filters) {
            $storageDir = storage_path();
            $filePath = $storageDir . DIRECTORY_SEPARATOR . $filename;

            $file = fopen($filePath, 'w');

            foreach($filters as $filter) {
                fprintf($file, "%s\n", $filter->rule);
            }

            fclose($file);
            
            return $filePath;
        }

        public function buildRuleset($namespace, $category, $type, $filterList) {
            $filename = $this->getFilename($namespace, $category, "$type.txt");

            $filePath = $this->buildFile($filename, TextFilteringRule::where('filter_list_id', '=', $filterList->id)->cursor());
            
            $filterList->file_sha1 = $this->getEtag($filePath);
            $filterList->save();
            
            return $filePath;
        }

        public function buildRuleData() {
            foreach(FilterList::cursor() as $list) {
                if (!is_null($list)) {

                    $listNamespace = $list->namespace;
                    $listCategory = $list->category;
                    $listType = $list->type;
                    $listId = $list->id;

                    switch ($listType) {
                        case 'Filters':{
                                $entryRelativePath = $this->getFilename($listNamespace, $listCategory, 'rules.txt', '/');
                                
                                $entryCachePath = $this->buildRuleset($listNamespace, $listCategory, 'rules', $list);
                            }
                            break;

                        case 'Triggers':{
                                $entryRelativePath = $this->getFilename($listNamespace, $listCategory, 'triggers.txt', '/');
                                
                                $entryCachePath = $this->buildRuleset($listNamespace, $listCategory, 'triggers', $list);
                            }
                            break;

                        case 'NLP':{

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

                                    $nlpEnabledCategories[$entryRelativePath] = array();
                                }

                                // Add this enabled NLP category to the already-discovered NLP model.
                                // We'll sort through each model path (as the key) and create a valid
                                // NLPConfigurationModel instance for each with a list of enabled
                                // categories (array value) later.
                                array_push($nlpEnabledCategories[$entryRelativePath], $listCategory);
                            }
                            break;

                        case 'VISUAL':{

                                $filter = TextFilteringRule::where('filter_list_id', '=', $listId)->first();

                                $entryRelativePath = '/' . $listNamespace . '/visual/' . '/visual.vv';
                            }
                            break;
                    }
                }
            }
        }
}

