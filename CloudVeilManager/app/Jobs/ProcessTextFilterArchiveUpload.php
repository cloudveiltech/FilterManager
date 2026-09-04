<?php

namespace App\Jobs;

use App\Models\FilterList;
use App\Models\FilterRulesManager;
use App\Models\Group;
use App\Models\GroupFilterAssignment;
use App\Services\FilterImportGate;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessTextFilterArchiveUpload implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const IMPORTABLE_ARCHIVE_FILENAMES = [
        'domains',
        'domains.txt',
        'urls',
        'urls.txt',
        'triggers',
        'triggers.txt',
        'rules',
        'rules.txt',
        'filters',
        'filters.txt',
    ];

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 1700;

    /**
     * Release a stranded uniqueness lock after two hours. Successful and
     * terminally failed jobs release the lock as soon as they finish.
     *
     * @var int
     */
    public $uniqueFor = 7200;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $listNamespace,
        public string $file,
        public bool $shouldOverwrite,
        public string $category = '',
        public ?string $disk = null,
    ) {}

    public function uniqueId(): string
    {
        $source = $this->category !== ''
            ? 'category:'.FilterImportGate::normalizeCategory($this->category)
            : 'file:'.$this->file;

        return hash('sha256', implode('|', [
            $this->listNamespace,
            $this->disk ?? 'local',
            $source,
        ]));
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

        $this->processArchive();

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

    private function processArchive(): void
    {
        if ($this->disk === null) {
            $this->processTextFilterArchive($this->listNamespace, $this->file, $this->shouldOverwrite);

            return;
        }

        $temporaryDirectory = storage_path('app/exports');
        $temporaryArchive = $temporaryDirectory.'/'.uniqid().'.zip';
        $sourceStream = null;
        $destinationStream = null;

        try {
            if (! is_dir($temporaryDirectory)
                && ! mkdir($temporaryDirectory, 0755, true)
                && ! is_dir($temporaryDirectory)) {
                throw new \RuntimeException("Unable to create temporary archive directory: {$temporaryDirectory}");
            }

            $sourceStream = Storage::disk($this->disk)->readStream($this->file);

            if (! is_resource($sourceStream)) {
                throw new \RuntimeException("Unable to read archive from disk [{$this->disk}]: {$this->file}");
            }

            $destinationStream = fopen($temporaryArchive, 'wb');

            if (! is_resource($destinationStream)) {
                throw new \RuntimeException("Unable to create temporary archive: {$temporaryArchive}");
            }

            if (stream_copy_to_stream($sourceStream, $destinationStream) === false) {
                throw new \RuntimeException("Unable to copy archive to temporary file: {$temporaryArchive}");
            }

            fclose($destinationStream);
            $destinationStream = null;
            fclose($sourceStream);
            $sourceStream = null;

            $this->processTextFilterArchive(
                $this->listNamespace,
                $temporaryArchive,
                $this->shouldOverwrite,
                $this->category !== '' ? $this->category : null,
                $this->category === '',
            );
        } finally {
            if (is_resource($destinationStream)) {
                fclose($destinationStream);
            }

            if (is_resource($sourceStream)) {
                fclose($sourceStream);
            }

            if (file_exists($temporaryArchive)) {
                @unlink($temporaryArchive);
            }
        }
    }

    /**
     * Processes an uploaded archive, extracting the text files inside and processing
     * them according to their type and category.
     * @param string $namespace The namespace of the parent filter list.
     * @param string $file The location of the file to be processed.
     * @param bool $overwrite Whether or not to overwrite.
     * @param string|null $expectedCategory Restrict the archive to one category when provided.
     * @param bool $honorImportEnabled Skip categories whose stored import policy is disabled.
     */
    public function processTextFilterArchive(
        string $namespace,
        string $tmpArchiveLoc,
        bool $overwrite,
        ?string $expectedCategory = null,
        bool $honorImportEnabled = false,
    )
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
        $categoryFilterLists = array();

        $zippedData = new \PharData($tmpArchiveLoc);
        $archiveCategories = $expectedCategory !== null || $honorImportEnabled
            ? $this->archiveCategories($zippedData, $tmpArchiveLoc)
            : [];
        $this->assertExpectedArchiveCategory($archiveCategories, $expectedCategory);
        $disabledCategories = [];

        if ($honorImportEnabled) {
            foreach ($archiveCategories as $archiveCategory) {
                if (FilterImportGate::storedCategoryImportState($namespace, $archiveCategory) === false) {
                    $disabledCategories[$archiveCategory] = true;
                }
            }
        }

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

                $categoryName = FilterImportGate::normalizeCategory($categoryName);

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
                    continue;
                }

                if (isset($disabledCategories[$categoryName])) {
                    Log::info('Skipping disabled import category: '.$categoryName);
                    continue;
                }

                if (!isset($fileCountByType[$finalListType])) {
                    $fileCountByType[$finalListType] = 0;
                }
                $fileCountByType[$finalListType]++;

                try {
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

                    // Register every output for this category before writing so a partial write can be removed.
                    $categoryFilterLists[$categoryName][$newFilterListEntry->id] = $newFilterListEntry;

                    $appendToEndOfFile = $fileCountByType[$finalListType] > 1;
                    $filterListManager->buildFileFromSpl($pharFileInfo->openFile('r'), $newFilterListEntry, $convertToAbp, $appendToEndOfFile);

                    $newFilterListEntry->touch();
                } catch (\Throwable $e) {
                    foreach ($categoryFilterLists[$categoryName] ?? [] as $categoryFilterList) {
                        $filterListManager->deleteFiles($categoryFilterList);
                    }

                    throw $e;
                }
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

    /**
     * @return array<int, string>
     */
    private function archiveCategories(
        \PharData $archive,
        string $archivePath,
    ): array {
        $categories = [];
        $archiveIterator = new \RecursiveIteratorIterator($archive, \RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($archiveIterator as $archiveFile) {
            if ($archiveFile->isDir()) {
                continue;
            }

            $filename = strtolower(basename($archiveFile->getPathname()));

            if (! in_array($filename, self::IMPORTABLE_ARCHIVE_FILENAMES, true)) {
                continue;
            }

            $category = strtolower(basename(dirname($archiveFile->getPathname())));

            if (in_array($category, ['/', '\\', '.', '..'], true)
                || strcasecmp($category, basename($archivePath)) === 0) {
                throw new \RuntimeException('The category export contains an importable file outside a category directory.');
            }

            $categories[FilterImportGate::normalizeCategory($category)] = true;
        }

        $categories = array_keys($categories);
        sort($categories);

        return $categories;
    }

    /**
     * @param  array<int, string>  $categories
     */
    private function assertExpectedArchiveCategory(array $categories, ?string $expectedCategory): void
    {
        if ($expectedCategory === null) {
            return;
        }

        $normalizedExpectedCategory = FilterImportGate::normalizeCategory($expectedCategory);

        if ($categories !== [$normalizedExpectedCategory]) {
            $foundCategories = $categories === [] ? '(none)' : implode(', ', $categories);

            throw new \RuntimeException(
                "The category export expected [{$normalizedExpectedCategory}] but contained [{$foundCategories}].",
            );
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
