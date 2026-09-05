<?php

namespace App\Jobs;

use App\Models\Group;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Rebuilds the cached data payload (data.zip + config_cache) for a single group.
 *
 * Group::rebuildGroupData() zips every ruleset assigned to a group, so a rebuild is expensive, and
 * many unrelated changes can each ask to rebuild the same group (a bulk group-assignment change
 * touching many filter lists, or a filter:discover batch whose finally() callback rebuilds every group
 * affected by the whole sweep). This job is ShouldBeUnique per group id: while a delayed rebuild for a
 * group is still pending, further dispatches for that same group are silently dropped instead of
 * queuing another rebuild, so a burst of requests for the same group collapses into a single rebuild
 * that picks up everything that changed in the meantime.
 *
 * Always dispatch this through RebuildGroupData::debounce() (or debounceMany()) rather than calling
 * dispatch() directly, so the delay window is applied consistently.
 */
class RebuildGroupData implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 1700;

    /**
     * Release a stranded uniqueness lock after this many seconds. Successful and terminally failed
     * jobs release the lock as soon as they finish; this is only a safety valve for a job that never
     * runs (e.g. a crashed worker holding the lock).
     *
     * @var int
     */
    public $uniqueFor = 3600;

    public function __construct(public int $groupId)
    {
    }

    public function uniqueId(): string
    {
        return 'group:'.$this->groupId;
    }

    /**
     * Dispatch a debounced rebuild for a single group.
     */
    public static function debounce(int $groupId): void
    {
        static::dispatch($groupId)->delay(now()->addSeconds(static::debounceSeconds()));
    }

    /**
     * Dispatch a debounced rebuild for each of the given groups.
     *
     * @param  array<int, int>  $groupIds
     */
    public static function debounceMany(array $groupIds): void
    {
        foreach (array_unique(array_map('intval', $groupIds)) as $groupId) {
            static::debounce($groupId);
        }
    }

    private static function debounceSeconds(): int
    {
        return (int) config('filter_imports.group_rebuild_debounce_seconds', 120);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $group = Group::find($this->groupId);

        if ($group === null) {
            return;
        }

        Log::debug('Running debounced RebuildGroupData job for group: '.$this->groupId);
        $group->rebuildGroupData();
        Log::debug('Finished debounced RebuildGroupData job for group: '.$this->groupId);
    }
}
