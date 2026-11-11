<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default group
    |--------------------------------------------------------------------------
    |
    | The group a filter check uses when no activation, user or group is given.
    |
    */

    'default_group_id' => (int) env('FILTER_CHECK_DEFAULT_GROUP_ID', 1),

    /*
    |--------------------------------------------------------------------------
    | Limits
    |--------------------------------------------------------------------------
    |
    | The most URLs one check may test. Every URL in a check shares a single
    | pass over the group's list files, so a batch costs about the same as one.
    |
    */

    'max_urls' => (int) env('FILTER_CHECK_MAX_URLS', 50),

    /*
    |--------------------------------------------------------------------------
    | grep
    |--------------------------------------------------------------------------
    |
    | The grep binary used to find the rules that could apply to a URL, and how
    | long one search may run, in seconds.
    |
    */

    'grep_binary' => env('FILTER_CHECK_GREP_BINARY', 'grep'),

    'grep_timeout' => (int) env('FILTER_CHECK_GREP_TIMEOUT', 60),

];
