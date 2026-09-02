<?php

// These categories must never be imported from the export bucket, even if filter_lists rows exist.
return [
    'deny' => ['Uncategorized', 'test', 'invalid'],
    'clock_tolerance_seconds' => 5,

    // Whether schedule:run sweeps the export bucket. Turn this off to stop
    // discovery without editing the schedule, or when filter:discover is
    // driven by its own crontab entry instead.
    'schedule_enabled' => env('FILTER_DISCOVER_SCHEDULE_ENABLED', true),
];
