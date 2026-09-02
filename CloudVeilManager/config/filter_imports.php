<?php

// These categories must never be imported from the export bucket, even if filter_lists rows exist.
return [
    // Comma separated in FILTER_IMPORT_DENY. Matching is case-insensitive, and an
    // empty value denies nothing.
    'deny' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('FILTER_IMPORT_DENY', 'Uncategorized,test,invalid')),
    ), static fn (string $category): bool => $category !== '')),
    'clock_tolerance_seconds' => 5,

    // Whether schedule:run sweeps the export bucket. Turn this off to stop
    // discovery without editing the schedule, or when filter:discover is
    // driven by its own crontab entry instead.
    'schedule_enabled' => env('FILTER_DISCOVER_SCHEDULE_ENABLED', true),
];
