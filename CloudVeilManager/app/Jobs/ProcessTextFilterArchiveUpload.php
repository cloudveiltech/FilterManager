<?php

namespace App\Jobs;

use App\Models\FilterList;
use App\Models\FilterRulesManager;
use App\Models\Group;
use App\Models\GroupFilterAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class ProcessTextFilterArchiveUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $listNamespace;
    public $file;
    public $shouldOverwrite;
    public $category;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 1700;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $listNamespace, string $file, bool $shouldOverwrite, string $category = '')
    {
        $this->listNamespace = $listNamespace;
        $this->file = $file;
        $this->shouldOverwrite = $shouldOverwrite;
        $this->category = $category;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('Running processTextFilterArchive Job.');
        $client = new Client();

        try {
            $payload = json_encode(
                [
                    'channel' => config('services.slack.channel.import'),
                    'text' => "Beginning " . $this->category . " File Import. File: " . $this->file . " Should Overwrite: " . $this->shouldOverwrite . " List: " . $this->listNamespace,
                    'username' => config('app.name')
                ]);

            $res = $client->request('POST', config('services.slack.url'),
                [
                    'body' => $payload
                ]
            );
        } catch (\Exception $e) {
            Log::error($e);
        }

        ProcessTextFilterArchiveUpload::processTextFilterArchive($this->listNamespace, $this->file, $this->shouldOverwrite);

        Log::info('Finished processTextFilterArchive Job.');

        try {
            $payload = json_encode(
                [
                    'channel' => config('services.slack.channel.import'),
                    'text' => "Completed " . $this->category . " File Import. File: " . $this->file . " Should Overwrite: " . $this->shouldOverwrite . " List: " . $this->listNamespace,
                    'username' => config('app.name')
                ]);

            $res = $client->request('POST', config('services.slack.url'),
                [
                    'body' => $payload
                ]
            );
        } catch (\Exception $e) {
            Log::error($e);
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
                $affectedGroups = array_merge($affectedGroups, ProcessTextFilterArchiveUpload::getGroupsAttachedToFilterId($newFilterListEntry->id));

                $appendToEndOfFile = $fileCountByType[$finalListType] > 1;
                $filterListManager->buildFileFromSpl($pharFileInfo->openFile('r'), $newFilterListEntry, $convertToAbp, $appendToEndOfFile);

                $newFilterListEntry->touch();
            }
        }

        // Force rebuild of group data for all affected groups.
        $affectedGroups = array_unique($affectedGroups);
        ProcessTextFilterArchiveUpload::forceRebuildOnGroups($affectedGroups);
        Log::info('Removing Archived File: ' . $tmpArchiveLoc);
        if (file_exists($tmpArchiveLoc)) {
            @unlink($tmpArchiveLoc);
        }
    }

    public static function forceRebuildOnGroups(array $arrOfGroupIds)
    {
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


    public static function getGroupsAttachedToFilterId(int $filterId): array
    {
        $ret = array();
        // Pull group assignment of this filter, if any.
        foreach (GroupFilterAssignment::where('filter_list_id', $filterId)->get() as $affectedList) {
            array_push($ret, $affectedList->group_id);
        }

        return $ret;
    }

}
