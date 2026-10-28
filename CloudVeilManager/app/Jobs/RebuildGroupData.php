<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Rebuilds the cached data payload (data.zip + config_cache) for a set of groups.
 *
 * Group::rebuildGroupData() zips every ruleset assigned to a group, so a rebuild is expensive and a
 * single assignment change from the filter-list side can touch many groups at once. Callers pass
 * only the groups whose assignment actually changed (see GroupFilterAssignmentService) and dispatch
 * this job rather than rebuilding inline, so the web request doesn't block on the zips.
 *
 * Note: ProcessTextFilterArchiveUpload::forceRebuildOnGroups() is a synchronous loop, not a queue
 * dispatch — it's safe to call from inside a queued job like this one, but not from a controller.
 */
class RebuildGroupData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 1700;

    /**
     * @var array<int, int>
     */
    public $groupIds;

    /**
     * @param  array<int, int>  $groupIds
     */
    public function __construct(array $groupIds)
    {
        $this->groupIds = array_values(array_unique(array_map('intval', $groupIds)));
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->groupIds === []) {
            return;
        }

        Log::info('Running RebuildGroupData job. Total Groups: ' . count($this->groupIds));

        ProcessTextFilterArchiveUpload::forceRebuildOnGroups($this->groupIds);

        Log::info('Finished RebuildGroupData job.');
    }
}
