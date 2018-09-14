<?php

namespace App {
    class GlobalFilterRules {
        public function getRuleDataPath(): string
        {
            $storageDir = storage_path();
            $rulesZipPath = $storageDir . DIRECTORY_SEPARATOR . 'global-rules.zip';
            return $rulesZipPath;
        }

        public function buildRuleData() {
            $rulesZipPath = $this->getRuleDataPath();

            $zip = new \ZipArchive();
            $zip->open($rulesZipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

            foreach(FilterList::cursor() as $list) {
                if (!is_null($list)) {

                    $listNamespace = $list->namespace;
                    $listCategory = $list->category;
                    $listType = $list->type;
                    $listId = $list->id;

                    switch ($listType) {
                        case 'Filters':{
                                $inMemFilterFile = '';
                                $filters = TextFilteringRule::where('filter_list_id', '=', $listId)->get();

                                foreach ($filters as $filter) {
                                    $inMemFilterFile .= $filter->rule . "\n";
                                }

                                $entryRelativePath = '/' . $listNamespace . '/' . $listCategory . '/rules.txt';

                                $zip->addFromString($entryRelativePath, $inMemFilterFile);
                            }
                            break;

                        case 'Triggers':{
                                $inMemFilterFile = '';
                                $filters = TextFilteringRule::where('filter_list_id', '=', $listId)->get();

                                foreach ($filters as $filter) {
                                    $inMemFilterFile .= $filter->rule . "\n";
                                }

                                $entryRelativePath = '/' . $listNamespace . '/' . $listCategory . '/triggers.txt';

                                $zip->addFromString($entryRelativePath, $inMemFilterFile);
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

                        case 'VISUAL':{

                                $filter = TextFilteringRule::where('filter_list_id', '=', $listId)->first();

                                $entryRelativePath = '/' . $listNamespace . '/visual/' . '/visual.vv';

                                $zip->addFromString($entryRelativePath, $filter->data);
                            }
                            break;
                    }
                }
            }

            $zip->close();
        }
    }
}
