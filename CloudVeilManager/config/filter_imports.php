<?php

return [
    // Whether a category may be imported is stored on its filter_lists rows
    // (the import_enabled column), so it can be changed from the admin UI and
    // is shared by every app pointed at this database.

    'clock_tolerance_seconds' => 5,

    // Whether schedule:run sweeps the export bucket. Turn this off to stop
    // discovery without editing the schedule, or when filter:discover is
    // driven by its own crontab entry instead.
    'schedule_enabled' => env('FILTER_DISCOVER_SCHEDULE_ENABLED', true),

    // The scheduler uses a cron minute step, so keep the interval within the
    // valid minute range. Fifteen minutes remains the default cadence.
    'schedule_interval_minutes' => max(
        1,
        min(59, (int) env('FILTER_DISCOVER_SCHEDULE_INTERVAL_MINUTES', 15)),
    ),
];
