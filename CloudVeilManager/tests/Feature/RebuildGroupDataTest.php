<?php

use App\Jobs\RebuildGroupData;
use Illuminate\Support\Facades\Queue;

test('debounce collapses repeated requests for the same group into one job', function () {
    config()->set('cache.default', 'array');
    Queue::fake();

    RebuildGroupData::debounce(42);
    RebuildGroupData::debounce(42);
    RebuildGroupData::debounceMany([42, 43, 43]);

    Queue::assertPushed(RebuildGroupData::class, 2);
    Queue::assertPushed(RebuildGroupData::class, static fn (RebuildGroupData $job): bool => $job->groupId === 42);
    Queue::assertPushed(RebuildGroupData::class, static fn (RebuildGroupData $job): bool => $job->groupId === 43);
});

test('debounce applies the configured delay', function () {
    config()->set('cache.default', 'array');
    config()->set('filter_imports.group_rebuild_debounce_seconds', 300);
    Queue::fake();

    $before = now();
    RebuildGroupData::debounce(7);

    Queue::assertPushed(RebuildGroupData::class, function (RebuildGroupData $job) use ($before): bool {
        // PendingDispatch exposes the delay via the job's `delay` property once queued.
        return $job->groupId === 7
            && $job->delay !== null
            && $job->delay->greaterThanOrEqualTo($before->clone()->addSeconds(299));
    });
});

test('a different group is not blocked by another group\'s pending debounce', function () {
    config()->set('cache.default', 'array');
    Queue::fake();

    RebuildGroupData::debounce(1);
    RebuildGroupData::debounce(2);

    Queue::assertPushed(RebuildGroupData::class, 2);
});
