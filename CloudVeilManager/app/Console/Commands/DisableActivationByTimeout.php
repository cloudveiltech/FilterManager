<?php

namespace App\Console\Commands;

use App\AppUserActivation;
use Illuminate\Console\Command;

class DisableActivationByTimeout extends Command
{
    const TIMEOUT_HOURS = 24*30*2;//2 months
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activation:disable';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Disable activation by timeout';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        AppUserActivation::whereRaw("last_sync_time < timestampadd(hour, ?, now())", ['-' . self::TIMEOUT_HOURS])->delete();
    }
}
