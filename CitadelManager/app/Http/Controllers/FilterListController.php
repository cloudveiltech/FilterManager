<?php

/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\FilterList;
use App\Group;
use App\GroupFilterAssignment;
use App\FilterRulesManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Jobs\ProcessTextFilterArchiveUpload;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;

class FilterListController extends Controller {

    /**
     * Trigger an automatic download and import.
     **/
    public function triggerUpdate(Request $request) {
        $timestamp = Carbon::now()->toIso8601ZuluString();
        $client = new Client();
        $filename = 'export.zip';
        if ($request->has('file')) {
            $filename = $request->input('file');
            $filename = preg_replace('/[^0-9a-zA-Z\-_\.]+/', '', $filename);
        }
        $results = 'Downloading File from ' . config('app.default_list_export_url') . $filename . '<br>';
        $response = $client->get(config('app.default_list_export_url') . $filename);
        $results .= 'Saving to: ' . $timestamp . '.zip<br>';
        Storage::put('export' . $timestamp . '.zip', $response->getBody());
        ProcessTextFilterArchiveUpload::dispatch('default', storage_path('app/export' . $timestamp . '.zip'), true);
        $results .= 'Import has been triggered.<br>';
        return response($results);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request) {
        $draw = $request->input('draw');
        $start = $request->input('start');
        $length = $request->input('length') ? $request->input('length') : 10;
        $search = $request->input('search')['value'];

        $order = $request->input('order')[0]['column'];
        $order_name = $request->input('columns')[intval($order)]['data'];
        $order_str = $request->input('order')[0]['dir'];

        $recordsTotal = FilterList::count();

        $query = FilterList::select('filter_lists.*')
            ->when($search, function ($query) use ($search) {
                return $query->where('filter_lists.category', 'like', "%$search%");
            })
            ->when(($order_name != 'entries_count'), function ($query) use ($order_str, $order_name) {
                return $query->orderBy($order_name, $order_str);
            }, function ($query) use ($order_str, $order_name) {

            });

        if ($order_name != "entries_count") {
            $recordsFilterTotal = $query->count();
            $rows = $query->offset($start)
                ->limit($length)
                ->get();
        } else {
            $rows = $query->get();
            $filter_rows = [];
            foreach ($rows as $key => $filter_item) {

                $filter_rows[] = array(
                    "id" => $filter_item->id,
                    "namespace" => $filter_item->namespace,
                    "category" => $filter_item->category,
                    "entries_count" => $filter_item->entries_count,
                    "type" => $filter_item->type,
                    "created_at" => $filter_item->created_at->toDateTimeString(),
                );
            }
            $sortArray = array();

            foreach ($filter_rows as $filter) {
                foreach ($filter as $key => $value) {
                    if (!isset($sortArray[$key])) {
                        $sortArray[$key] = array();
                    }
                    $sortArray[$key][] = $value;
                }
            }
            if ($order_str == "desc") {
                array_multisort($sortArray[$order_name], SORT_DESC, $filter_rows);
            } else {
                array_multisort($sortArray[$order_name], SORT_ASC, $filter_rows);
            }
            $rows = array_slice($filter_rows, $start, $length, false);
            $recordsFilterTotal = count($filter_rows);
        }

        return response()->json([
            "draw" => intval($draw),
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsFilterTotal,
            "data" => $rows,
        ]);
    }

    public function get_filters() {
        return FilterList::all();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        // No forms here kids.
        return response('', 405);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        // Right now, creation/update/storage is done simply by upload of raw
        // rules.
        return response('', 405);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
        // No forms here kids.
        return response('', 405);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        // No forms here kids.
        return response('', 405);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        // Right now, creation/update/storage is done simply by upload of raw
        // rules.
        return response('', 405);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        $affectedGroups = array();

        $existingList = FilterList::where('id', $id)->first();
        $filterRulesManager = new FilterRulesManager();
        if (!is_null($existingList)) {
            $filterRulesManager->deleteFiles($existingList);
            // Pull group assignment of this filter, if any, then delete them.
            $affectedGroups = array_merge($affectedGroups, $this->getGroupsAttachedToFilterId($existingList->id));
            GroupFilterAssignment::where('filter_list_id', $existingList->id)->delete();

            // It was only a text list, so just delete this entry.
            $existingList->delete();
        }

        // Force rebuild of group data for all affected groups.
        $affectedGroups = array_unique($affectedGroups);
        $this->forceRebuildOnGroups($affectedGroups);

        return response('', 204);
    }

    private function getGroupsAttachedToFilterId(int $filterId): array {
        $ret = array();
        // Pull group assignment of this filter, if any.
        foreach (GroupFilterAssignment::where('filter_list_id', $filterId)->get() as $affectedList) {
            array_push($ret, $affectedList->group_id);
        }

        return $ret;
    }

    private function forceRebuildOnGroups(array $arrOfGroupIds) {
        Log::debug('Rebuilding Group Data.  Total Groups: ' . count($arrOfGroupIds));
        $count = 0;
        foreach ($arrOfGroupIds as $groupId) {
            $thisGroup = Group::where('id', $groupId)->first();

            if (!is_null($thisGroup)) {
                Log::debug('Rebuilding Group ' . $count . ' of ' . count($arrOfGroupIds) . ' --- ' . $thisGroup->name);
                $thisGroup->rebuildGroupData();
            }
            $count++;
        }
    }

    /**
     * Processes an uploaded filter list file of any supported kind. This could
     * be a zip with text rules
     * for image filtering.
     * @param Request $request
     * @return type
     */
    public function processUploadedFilterLists(Request $request) {
        $this->validate($request, [
            'overwrite' => 'required|boolean',
            'namespace' => 'required|string|min:1|max:64',
        ]);

        //error_log($file->getClientOriginalName());
        //error_log($file->getClientOriginalExtension());
        //error_log($file->getExtension());

        $listFile = $request->file();
        $shouldOverwrite = $request->get('overwrite');
        $listNamespace = preg_replace('/\s+/', '_', strtolower($request->get('namespace')));

        $success = false;

        foreach ($listFile as $file) {
            switch (strtolower($file->getClientOriginalExtension())) {
                case 'zip':
                    {
                        $storedFile = $file->store('zip_uploads');
                        $success = ProcessTextFilterArchiveUpload::dispatch(
                            $listNamespace,
                            storage_path('app/' . $storedFile),
                            $shouldOverwrite
                        );
                    }
                    break;
                default:
                {
                    return response('Uploaded file type not supported.', 400);
                }
            }
        }

        if (!$success) {
            response('Failure while processing uploaded file.', 500);
        }

        return response('', 204);
    }

    /**
     * Deletes all filter lists and their entries within the given namespace, and
     * if supplied, category.
     * @param type $namespace The namespace to delete all filtering lists from. Required.
     * @param type $type The type of filter list to constrain the mass deletion to. Optional.
     * @return type             Void
     */
    public function deleteAllListsInNamespace($namespace, $type = null) {
        $affectedGroups = array();

        $filterRulesManager = new FilterRulesManager();
        if (!is_null($namespace)) {
            $existingLists = null;
            if (!is_null($type)) {
                $existingLists = FilterList::where(['namespace' => $namespace, 'type' => $type])->get();
            } else {
                $existingLists = FilterList::where('namespace', $namespace)->get();
            }

            if (!is_null($existingLists)) {
                foreach ($existingLists as $existingList) {
                    $filterRulesManager->deleteFiles($existingList);

                    // Pull group assignment of this filter, if any, then delete them.
                    $affectedGroups = array_merge($affectedGroups, $this->getGroupsAttachedToFilterId($existingList->id));
                    GroupFilterAssignment::where('filter_list_id', $existingList->id)->delete();

                    $existingList->delete();
                }
            }

            // Force rebuild of group data for all affected groups.
            $affectedGroups = array_unique($affectedGroups);
            $this->forceRebuildOnGroups($affectedGroups);

            return response('', 204);
        }

        return response('Namespace parameter, which is required, was null.', 400);
    }

    private function loopIterator($itr, $leafFunction) {
        if (!$itr->hasChildren()) {
            $leafFunction($itr->current(), true);
        } else {
            $leafFunction($itr->current(), false);

            foreach ($itr->getChildren() as $childItr) {
                $this->loopIterator($itr, $leafFunction);
            }

        }
    }

    /**
     * Processes an uploaded archive, extracting the text files inside and processing
     * them according to their type and category.
     * @param string $namespace The namespace of the parent filter list.
     * @param string $file The location of the file to be processed.
     * @param bool $overwrite Whether or not to overwrite.
     */
    public function processTextFilterArchive(string $namespace, string $tmpArchiveLoc, bool $overwrite) {
        $affectedGroups = array();

        // Zipped filter lists are expected to use the following
        // structure:
        // /  <-- ROOT
        // /category_name/
        // /category_name/domains[none|.txt]
        // /category_name/urls[none|.txt]
        // /category_name/filters[none|.txt]
        // /category_name/rules[none|.txt]
        Log::info('Processing textFilterArchive located at: ' . $tmpArchiveLoc);
        $tmpArchiveDir = "$tmpArchiveLoc-dir";

        // Sometimes, a category can have more than one file that is treated
        // the same. domains and urls files will both get pushed into a list
        // of type Filters. If we run the delete op in our foreach here,
        // we will end up purging our lists more than once when overwrite is
        // set to true. So, we keep a map of all list ID's that we've already
        // purged so we only do this once.
        $purgedCategories = array();

        $zippedData = new \PharData($tmpArchiveLoc);
        $filterListManager = new FilterRulesManager();
        $pharIterator = new \RecursiveIteratorIterator($zippedData, \RecursiveIteratorIterator::CHILD_FIRST);
        $fileCountByType = [];

        foreach ($pharIterator as $pharFileInfo) {
            Log::debug("Phar internal data location " . $pharFileInfo->getPath());

            if (!$pharFileInfo->isDir()) {
                $categoryName = strtolower(basename(dirname($pharFileInfo->getPathname())));

                if ($categoryName == '/' || $categoryName == '\\' || $categoryName == '.' || $categoryName == '..') {
                    // This is an improperly formatted zip.
                    // This is a file inside the root directory.
                    Log::debug("improperly formatted");
                    continue;
                }

                if (strcasecmp($categoryName, basename($tmpArchiveLoc)) == 0) {
                    // This is an improperly formatted zip. This means that we have
                    // filter/trigger model stuff in the root of the zip structure
                    // and this cannot be allowed.
                    Log::debug("improperly formatted2");
                    continue;
                }

                // We have to limit the length of our string to the max length
                // constraint of our DB field.
                if (strlen($categoryName) > 64) {
                    $categoryName = substr($categoryName, 0, 64);
                }

                // Ensure no spaces etc in cat name.
                $categoryName = preg_replace('/\s+/', '_', strtolower($categoryName));

                $fileName = strtolower(basename($pharFileInfo->getPathname()));
                Log::debug("filename = $fileName");

                $finalListType = null;
                $convertToAbp = false;
                switch ($fileName) {
                    case 'domains':
                    case 'domains.txt':
                    case 'urls':
                    case 'urls.txt':
                        {
                            // These rules get converted to ABP filters.
                            $finalListType = 'Filters';
                            $convertToAbp = true;
                        }
                        break;

                    case 'triggers':
                    case 'triggers.txt':
                        {
                            // These rules are untouched.
                            $finalListType = 'Triggers';
                        }
                        break;

                    case 'rules':
                    case 'filters':
                    case 'filters.txt':
                    case 'rules.txt':
                        {
                            // These rules are untouched. Assumed to already
                            // be in ABP filter format.
                            $finalListType = 'Filters';
                        }
                        break;
                }

                if (!isset($fileCountByType[$finalListType])) {
                    $fileCountByType[$finalListType] = 0;
                }
                $fileCountByType[$finalListType]++;

                if (is_null($finalListType)) {
                    Log::debug("invalid/improperly named/unrecognized file");
                    continue;
                }

                if ($overwrite) {
                    Log::info('Overwriting: ' . $namespace . ' Category: ' . $categoryName);

                    $existingList = FilterList::where([['namespace', '=', $namespace], ['category', '=', $categoryName], ['type', '=', $finalListType]])->first();
                    if (!is_null($existingList) && !in_array($existingList->id, $purgedCategories)) {
                        $filterListManager->deleteFiles($existingList);
                        array_push($purgedCategories, $existingList->id);
                    }
                }

                $newFilterListEntry = FilterList::firstOrCreate(['namespace' => $namespace, 'category' => $categoryName, 'type' => $finalListType]);

                if ($overwrite && $newFilterListEntry->wasRecentlyCreated) {
                    array_push($purgedCategories, $newFilterListEntry->id);
                }

                // In case this is existing, pull group assignment of this filter.
                $affectedGroups = array_merge($affectedGroups, $this->getGroupsAttachedToFilterId($newFilterListEntry->id));

                $appendToEndOfFile = $fileCountByType[$finalListType] > 1;
                $filterListManager->buildFileFromSpl($pharFileInfo->openFile('r'), $newFilterListEntry, $convertToAbp, $appendToEndOfFile);

                $newFilterListEntry->touch();
            }
        }

        // Force rebuild of group data for all affected groups.
        $affectedGroups = array_unique($affectedGroups);
        $this->forceRebuildOnGroups($affectedGroups);
        Log::info('Removing Archived File: ' . $tmpArchiveLoc);
        if (file_exists($tmpArchiveLoc)) {
            @unlink($tmpArchiveLoc);
        }
    }
}
