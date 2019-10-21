<?php

namespace App\Console;

use App\AppUserActivation;
use App\Exceptions\UpdateProbablyFailedException;
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
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $this->scheduleDeletingDeactivationRequests($schedule);
        $this->scheduleUpdateBypassUsed($schedule);
        $this->scheduleQueueWork($schedule);
        $this->scheduleSentryUpdateAlerts($schedule);
    }

    private function scheduleDeletingDeactivationRequests(Schedule $schedule)
    {
        $schedule->call(function () {
            /* Delete deactivation requests which are haven't been touched for more than X days. */
            DB::table('deactivation_requests')
                ->where('created_at', '<', Carbon::now()->subDays(config('app.deactivation_request_expiration'))->format('Y-m-d H:i:s'))
                ->where('updated_at', '<', Carbon::now()->subDays(config('app.deactivation_request_expiration'))->format('Y-m-d H:i:s'))
                ->delete();
        })->daily();
    }

    private function scheduleUpdateBypassUsed(Schedule $schedule)
    {
        $schedule->call(function () {
            /* Reset bypass_used count */
            DB::table('app_user_activations')
                ->update(['bypass_used' => 0]);
        })->dailyAt(config('app.bypass_used_delete_time'));
    }

    private function scheduleQueueWork(Schedule $schedule)
    {
        $schedule->command('queue:work --daemon --tries=3 --timeout=2400')
            ->name('queue')
            ->everyMinute()
            ->withoutOverlapping();
    }

    private function scheduleSentryUpdateAlerts(Schedule $schedule)
    {
        //alert users who didn't sync after update
        $schedule->command("sentry:report_update_failed")->hourly();//dailyAt("00:00");
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
        $this->load(__DIR__ . '/Commands');
    }
}
