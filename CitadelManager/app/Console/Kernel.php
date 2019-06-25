<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
       Commands\FilterUpload::class,
       //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            /* Delete deactivation requests which are haven't been touched for more than X days. */
            DB::table('deactivation_requests')
                ->where('created_at', '<', Carbon::now()->subDays(config('app.deactivation_request_expiration'))->format('Y-m-d H:i:s') )
                ->where('updated_at', '<', Carbon::now()->subDays(config('app.deactivation_request_expiration'))->format('Y-m-d H:i:s') )
                ->delete();
        })->daily();

        $schedule->call(function () {
            /* Reset bypass_used count */
            DB::table('app_user_activations')
                ->update(['bypass_used' => 0]);
        })->dailyAt(config('app.bypass_used_delete_time'));

        $schedule->command('queue:work --daemon --tries=3 --timeout=2400')
            ->name('queue')
            ->everyMinute()
            ->withoutOverlapping();
    } 

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}
