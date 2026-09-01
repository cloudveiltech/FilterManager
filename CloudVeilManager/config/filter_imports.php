<?php

// These categories must never be imported from the export bucket, even if filter_lists rows exist.
return [
    'deny' => ['Uncategorized', 'test', 'invalid'],
    'clock_tolerance_seconds' => 5,
];
