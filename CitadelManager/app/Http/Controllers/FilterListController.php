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
use App\ImageFilteringRule;
use App\NlpFilteringRule;
use App\TextFilteringRule;
use App\FilterRulesManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Jobs\ProcessTextFilterArchiveUpload;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;

class FilterListController extends Controller
{

    /**
     * Trigger an automatic download and import.
     **/
    public function triggerUpdate(Request $request)
    {
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
    public function index(Request $request)
    {
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

    public function get_filters()
    {
        return FilterList::all();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // No forms here kids.
        return response('', 405);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Right now, creation/update/storage is done simply by upload of raw
        // rules.
        return response('', 405);
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // No forms here kids.
        return response('', 405);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        // No forms here kids.
        return response('', 405);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // Right now, creation/update/storage is done simply by upload of raw
        // rules.
        return response('', 405);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

        $affectedGroups = array();

        $existingList = FilterList::where('id', $id)->first();
        if (!is_null($existingList)) {

            // It's okay to just straight up call a delete here.
            TextFilteringRule::where('filter_list_id', $existingList->id)->delete();

            // Pull group assignment of this filter, if any, then delete them.
            $affectedGroups = array_merge($affectedGroups, $this->getGroupsAttachedToFilterId($existingList->id));
            GroupFilterAssignment::where('filter_list_id', $existingList->id)->delete();

            switch ($existingList->type) {
                // For NLP and Visual models, we have to delete all entries.
                case 'VISUAL':
                case 'NLP':
                    {
                        $existingSiblingLists = FilterList::where(['namespace' => $existingList->namespace, 'type' => $existingList->type])->get();
                        if (!is_null($existingSiblingLists)) {
                            foreach ($existingSiblingLists as $existingSibling) {
                                NlpFilteringRule::where('filter_list_id', $existingSibling->id)->delete();
                                ImageFilteringRule::where('filter_list_id', $existingSibling->id)->delete();

                                // Pull group assignment of this filter, if any, then delete them.
                                $affectedGroups = array_merge($affectedGroups, $this->getGroupsAttachedToFilterId($existingSibling->id));
                                GroupFilterAssignment::where('filter_list_id', $existingSibling->id)->delete();

                                $existingSibling->delete();
                            }
                        }
                    }
                    break;

                default:
                    {
                        // It was only a text list, so just delete this entry.
                        $existingList->delete();
                    }
                    break;
            }
        }

        // Force rebuild of group data for all affected groups.
        $affectedGroups = array_unique($affectedGroups);
        $this->forceRebuildOnGroups($affectedGroups);

        return response('', 204);
    }

    private function getGroupsAttachedToFilterId(int $filterId): array
    {
        $ret = array();
        // Pull group assignment of this filter, if any.
        foreach (GroupFilterAssignment::where('filter_list_id', $filterId)->get() as $affectedList) {
            array_push($ret, $affectedList->group_id);
        }

        return $ret;
    }

    private function forceRebuildOnGroups(array $arrOfGroupIds)
    {
        $globalFilterRules = new FilterRulesManager();
        $globalFilterRules->buildRuleData();

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
     * be a zip with text rules, a NLP model file, or a visual vocabulary file
     * for image filtering.
     * @param Request $request
     * @return type
     */
    public function processUploadedFilterLists(Request $request)
    {
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

                case 'model':
                    {

                        $success = $this->processNlpModel($listNamespace, $file, $shouldOverwrite);
                    }
                    break;

                case 'vv':
                    {
                        $success = $this->processVisualModel($listNamespace, $file, $shouldOverwrite);
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
    public function deleteAllListsInNamespace($namespace, $type = null)
    {

        $affectedGroups = array();

        if (!is_null($namespace)) {
            $existingLists = null;
            if (!is_null($type)) {
                $existingLists = FilterList::where(['namespace' => $namespace, 'type' => $type])->get();
            } else {
                $existingLists = FilterList::where('namespace', $namespace)->get();
            }

            if (!is_null($existingLists)) {
                foreach ($existingLists as $existingList) {
                    TextFilteringRule::where('filter_list_id', $existingList->id)->delete();
                    NlpFilteringRule::where('filter_list_id', $existingList->id)->delete();
                    ImageFilteringRule::where('filter_list_id', $existingList->id)->delete();

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

    private function loopIterator($itr, $leafFunction)
    {
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
    public function processTextFilterArchive(string $namespace, string $tmpArchiveLoc, bool $overwrite)
    {

        $totalTime = 0;

        $affectedGroups = array();

        // Zipped filter lists are expected to use the following
        // structure:
        // /  <-- ROOT
        // /category_name/
        // /category_name/domains[none|.txt]
        // /category_name/urls[none|.txt]
        // /category_name/filters[none|.txt]
        // /category_name/rules[none|.txt]

        //$storageDir = storage_path();
        //$tmpArchiveLoc = $storageDir . DIRECTORY_SEPARATOR . basename($file->getPathname()) . '.' . $file->getClientOriginalExtension();
        //$tmpArchiveLoc = basename($file->getPathname()) . '.' . $file->getClientOriginalExtension();
        Log::info('Processing textFilterArchive located at: ' . $tmpArchiveLoc);
        $tmpArchiveDir = "$tmpArchiveLoc-dir";

        //move_uploaded_file($file->getPathname(), $tmpArchiveLoc);

        // Sometimes, a category can have more than one file that is treated
        // the same. domains and urls files will both get pushed into a list
        // of type Filters. If we run the delete op in our foreach here,
        // we will end up purging our lists more than once when overwrite is
        // set to true. So, we keep a map of all list ID's that we've already
        // purged so we only do this once.
        //
        // This is not necessary for other types of filter data, such as NLP
        // models, because there can only be 1 per category.
        $purgedCategories = array();

        $zippedData = new \PharData($tmpArchiveLoc);

        $pharIterator = new \RecursiveIteratorIterator($zippedData, \RecursiveIteratorIterator::CHILD_FIRST);
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
                    // filter/trigger/nlp model stuff in the root of the zip structure
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

                if (is_null($finalListType)) {
                    Log::debug("invalid/improperly named/unrecognized file");
                    // Invalid/improperly named/unrecognized file.
                    continue;
                }

                // Delete existing if overwrite is true.
                if ($overwrite) {
                    Log::info('Overwriting: ' . $namespace . ' Category: ' . $categoryName);

                    $existingList = FilterList::where([['namespace', '=', $namespace], ['category', '=', $categoryName], ['type', '=', $finalListType]])->first();
                    if (!is_null($existingList) && !in_array($existingList->id, $purgedCategories)) {
                        TextFilteringRule::where('filter_list_id', '=', $existingList->id)->forceDelete();

                        array_push($purgedCategories, $existingList->id);

                        // DON'T DELETE THIS ACTUAL FILTER LIST ENTRY!!
                        // If we do that, then we have to manually rebuild
                        // filter list assignments. Leave it the same. Only
                        // delete actual text lines for it.
                    }
                }

                $newFilterListEntry = FilterList::firstOrCreate(['namespace' => $namespace, 'category' => $categoryName, 'type' => $finalListType]);

                // We have to do this for the event where the user selects overwrite on the upload,
                // yet one or more of the lists didn't exist already. Otherwise, same problem will
                // arise as described in the comments above purgedCategories var creation above.
                if ($overwrite && $newFilterListEntry->wasRecentlyCreated) {
                    array_push($purgedCategories, $newFilterListEntry->id);
                }

                // In case this is existing, pull group assignment of this filter.
                $affectedGroups = array_merge($affectedGroups, $this->getGroupsAttachedToFilterId($newFilterListEntry->id));

                $this->processTextFilterFile($pharFileInfo->openFile('r'), $convertToAbp, $newFilterListEntry->id);

                // Update updated_at timestamps.
                $newFilterListEntry->touch();

                // This means that the list is a raw list of domains/urls.
                // We will also convert these to triggers.
                if ($convertToAbp == true) {
                    Log::debug('Converting List to ABP format');
                    $newFilterListEntry = FilterList::firstOrCreate(['namespace' => $namespace, 'category' => $categoryName, 'type' => 'Triggers']);

                    // We have to do this for the event where the user selects overwrite on the upload,
                    // yet one or more of the lists didn't exist already. Otherwise, same problem will
                    // arise as described in the comments above purgedCategories var creation above.
                    if ($overwrite && $newFilterListEntry->wasRecentlyCreated) {
                        array_push($purgedCategories, $newFilterListEntry->id);
                    }

                    if ($overwrite) {
                        if (!is_null($newFilterListEntry) && !in_array($newFilterListEntry->id, $purgedCategories)) {

                            TextFilteringRule::where('filter_list_id', '=', $newFilterListEntry->id)->forceDelete();

                            array_push($purgedCategories, $newFilterListEntry->id);
                            // DON'T DELETE THIS ACTUAL FILTER LIST ENTRY!!
                            // If we do that, then we have to manually rebuild
                            // filter list assignments. Leave it the same. Only
                            // delete actual text lines for it.
                        }
                    }

                    // In case this is existing, pull group assignment of this filter.
                    $affectedGroups = array_merge($affectedGroups, $this->getGroupsAttachedToFilterId($newFilterListEntry->id));

                    $listCount = $this->processTextFilterFile($pharFileInfo->openFile('r'), false, $newFilterListEntry->id);

                    // Update updated_at timestamps.
                    $newFilterListEntry->entries_count = $listCount;
                    $newFilterListEntry->touch();
                }


                $newFilterListEntry->updateEntriesCount();
            }
        }

        // Force rebuild of group data for all affected groups.
        $affectedGroups = array_unique($affectedGroups);
        $this->forceRebuildOnGroups($affectedGroups);
        Log::info('Removing Archived File: ' . $tmpArchiveLoc);
        if (file_exists($tmpArchiveLoc)) {
            unlink($tmpArchiveLoc);
        }
    }

    /**
     * Processes the supplied text file line by line, according to its type,
     * generates rules from those lines and stores them.
     * @param \SplFileObject $file The source text file.
     * @param bool $convertToAbp Whether or not to format the rule lines as ABP filters.
     * @param int $parentListId The DB ID of the parent filter list.
     */
    private function processTextFilterFile(\SplFileObject $file, bool $convertToAbp, int $parentListId)
    {
        Log::debug("processTextFilterFile: " . $file->getFilename() . ' ' . $file->getPath());

        try {
            $lineFeedFunc = null;

            switch ($convertToAbp) {
                case true:
                    {
                        $lineFeedFunc = function (string $in): string {
                            return $this->formatStringAsAbpFilter(trim($in));
                        };
                    }
                    break;

                case false:
                    {
                        $lineFeedFunc = function (string $in): string {
                            return trim($in);
                        };
                    }
                    break;
            }

            $fillArr = array();
            $createdAt = Carbon::now();
            $updatedAt = Carbon::now();

            $count = 0;
            $lineCount = 0;

            foreach ($file as $lineNumber => $content) {

                $content = $lineFeedFunc($content);

                Log::debug('Line: ' . $lineCount . ' Content: ' . $content);
                if (strlen($content) > 0) {
                    $fillArr[] = ['filter_list_id' => $parentListId, 'sha1' => sha1($content), 'rule' => $content, 'created_at' => $createdAt, 'updated_at' => $updatedAt];
                    $count++;
                }

                // Doing a mass insert of 5K at a time seems to be best.
                if ($count > 4999) {
                    Log::debug('Writing 5000 lines into DB: ' . $parentListId);
//                    Log::debug($fillArr);
                    $results = TextFilteringRule::insertIgnore($fillArr);
//                    Log::debug($results);

                    $fillArr = array();
                    $count = 0;
                }
                $lineCount++;
            }
            if ($lineCount === 0) {
                Log::error('No lines in this file.');
            }

            if ($count > 0) {
//                Log::debug($fillArr);
                $results = TextFilteringRule::insertIgnore($fillArr);
//                Log::debug($results);
                $fillArr = array();
                $count = 0;
            }
        } catch (Exception $ex) {
            Log::error("Error occurred while processing text filter file $ex");
            throw $ex;
        }
        return $lineCount;
    }

    /**
     * Assuming that the input is a URL or just plain domain name, we convert
     * this string into an ABP formatted rule with an anchored domain.
     *
     * @param string $input
     * @return string
     */
    private function formatStringAsAbpFilter(string $input): string
    {
        if (strlen($input) <= 0) {
            return $input;
        }

        return '||' . str_replace('/', '^', $input);
    }

    /**
     * Processes the supplied NLP model and stores it.
     * @param string $namespace
     * @param UploadedFile $file
     * @param bool $overwrite
     */
    private function processNlpModel(string $namespace, UploadedFile $file, bool $overwrite)
    {

        $affectedGroups = array();

        // Because of the nature of this type of list, we have no choice
        // but to "overwrite" every time.
        $existingLists = FilterList::where(['namespace' => $namespace, 'type' => 'NLP'])->get();

        if (!is_null($existingLists)) {
            foreach ($existingLists as $existingList) {
                NlpFilteringRule::where('filter_list_id', $existingList->id)->delete();

                // Pull group assignment of this filter, if any, then delete them.
                $affectedGroups = array_merge($affectedGroups, $this->getGroupsAttachedToFilterId($existingList->id));
                GroupFilterAssignment::where('filter_list_id', $existingList->id)->delete();

                $existingList->delete();
            }
        }

        // So, we have to do something a little strange here with model files
        // like NLP. Because it's just a single binary model, but with multiple
        // nested categories, we need to create a new list entry for each
        // category, but only inject/store the model once.
        //
        // Basically, no matter what categories a person selects, all categories
        // will be included, because it's packed into a single binary.
        //
        // As such, we also need to handle deletion of such filters differently
        // than a plain text list. Any deletion of any category must result
        // in the deletion of all categories, again, because of a single
        // packed binary.

        $nlpPrinter = resource_path('util' . DIRECTORY_SEPARATOR . 'NLPCategoryPrinter.jar');

        $modelFilePath = $file->getPathname();
        $allCatsString = shell_exec(escapeshellcmd("java -jar $nlpPrinter $modelFilePath"));

        $allNlpCats = array();
        $separator = "\r\n";
        $line = strtok($allCatsString, $separator);

        while ($line !== false) {
            array_push($allNlpCats, trim($line));
            $line = strtok($separator);
        }

        if (count($allNlpCats) <= 0) {
            error_log('When parsing NLP categories, received no results.');
            return;
        }

        // Keep the first result ALWAYS, to prevent us from accidently
        // bypassing our UNIQUE contraints and storing the same model
        // twice or more. The NLP printer util should consistently
        // print out categories in the same order.
        $firstId = null;
        foreach ($allNlpCats as $nlpCat) {
            $newFilterListEntry = FilterList::firstOrCreate(['namespace' => $namespace, 'category' => trim($nlpCat), 'type' => 'NLP']);

            // Update updated_at timestamps.
            $newFilterListEntry->touch();

            if (is_null($firstId)) {
                $firstId = $newFilterListEntry->id;
            }
        }

        $hash = sha1_file($modelFilePath);
        $opened = $file->openFile('r');

        $createdAt = Carbon::now();
        $updatedAt = Carbon::now();

        NlpFilteringRule::insertIgnore(
            [
                'filter_list_id' => $firstId,
                'sha1' => $hash,
                'data' => $opened->fread($opened->getSize()),
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ]);

        // Force rebuild of group data for all affected groups.
        $affectedGroups = array_unique($affectedGroups);
        $this->forceRebuildOnGroups($affectedGroups);
    }

    /**
     * Processes the supplied visual vocabulary and stores it.
     * @param string $namespace
     * @param UploadedFile $file
     * @param bool $overwrite
     */
    private function processVisualModel(string $namespace, UploadedFile $file, bool $overwrite)
    {
        // Delete existing if overwrite is true.
        if ($overwrite) {
            $existingLists = ImageFilteringRule::where(['namespace' => $namespace, 'type' => 'VISUAL'])->get();
            if (!is_null($existingLists)) {
                foreach ($existingLists as $existingList) {
                    ImageFilteringRule::where('filter_list_id', $existingList->id)->delete();
                    $existingList->delete();
                }
            }
        }

        // XXX TODO - Logic TBD
        return;

        $hash = sha1_file($file->getPathname());
        $opened = $file->openFile('r');

        $createdAt = Carbon::now();
        $updatedAt = Carbon::now();

        $newFilterListEntry = FilterList::firstOrCreate(['namespace' => $namespace, 'category' => 'VISUAL', 'type' => 'VISUAL']);

        // Update updated_at timestamps.
        $newFilterListEntry->touch();

        ImageFilteringRule::insertIgnore(
            [
                'filter_list_id' => $newFilterListEntry->id,
                'sha1' => $hash,
                'data' => $opened->fread($opened->getSize()),
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ]);
    }
}
