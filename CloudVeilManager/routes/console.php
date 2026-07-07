<?php

/*
 * Copyright © 2017 Jesse Nicholson
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Schedule::command("sentry:report_update_failed")->hourly();//dailyAt("00:00");

Schedule::call(function () {
    /* Delete deactivation requests which are haven't been touched for more than X days. */
    DB::table('deactivation_requests')
        ->where('created_at', '<', Carbon::now()->subDays(config('app.deactivation_request_expiration'))->format('Y-m-d H:i:s'))
        ->where('updated_at', '<', Carbon::now()->subDays(config('app.deactivation_request_expiration'))->format('Y-m-d H:i:s'))
        ->delete();
})->daily();

Schedule::call(function () {
    /* Reset bypass_used count */
    DB::table('app_user_activations')
        ->update(['bypass_used' => 0]);
})->dailyAt(config('app.bypass_used_delete_time'));

Schedule::command('queue:work --daemon --tries=3 --timeout=2400')
    ->name('queue')
    ->everyMinute()
    ->withoutOverlapping();
