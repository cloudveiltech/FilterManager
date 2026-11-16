<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Rebuilds the compiled client configuration (config_cache) for a single group.
 *
 * Saving one popular app can touch hundreds of groups. Use dispatchForGroups() rather than
 * dispatching directly: it queues one job per group, and ShouldBeUniqueUntilProcessing drops a
 * dispatch while that group already has a rebuild waiting. Repeated saves therefore collapse into a
 * single pending rebuild per group instead of piling up. The lock is released as soon as the job
 * starts, so a change made during a rebuild still queues a fresh one and nothing is left stale.
 *
 * Note: ProcessTextFilterArchiveUpload::forceRebuildOnGroups() is a synchronous loop, not a queue
 * dispatch — it's safe to call from inside a queued job like this one, but not from a controller.
 */
class RebuildGroupData implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 1700;

    /**
     * Safety net: if a queued job is lost, its uniqueness lock expires after this many seconds.
     *
     * @var int
     */
    public $uniqueFor = 7200;

    public ?int $groupId = null;

    /**
     * Only set on jobs queued before rebuilds became one-per-group. Those payloads are still in
     * the queue after a deploy, so handle() fans them out into deduplicated per-group jobs.
     *
     * @var array<int, int>
     */
    public array $groupIds = [];

    public function __construct(int $groupId)
    {
        $this->groupId = $groupId;
    }

    /**
     * Queue one deduplicated rebuild per group.
     *
     * @param  array<int, int|string>  $groupIds
     */
    public static function dispatchForGroups(array $groupIds): void
    {
        foreach (array_unique(array_map('intval', $groupIds)) as $groupId) {
            static::dispatch($groupId);
        }
    }

    public function uniqueId(): string
    {
        return (string) ($this->groupId ?? 'legacy');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->groupIds !== []) {
            Log::info('Fanning out legacy RebuildGroupData job. Total Groups: '.count($this->groupIds));
            static::dispatchForGroups($this->groupIds);

            return;
        }

        if ($this->groupId === null) {
            return;
        }

        Log::info('Running RebuildGroupData job for group '.$this->groupId);

        ProcessTextFilterArchiveUpload::forceRebuildOnGroups([$this->groupId]);

        Log::info('Finished RebuildGroupData job for group '.$this->groupId);
    }
}
