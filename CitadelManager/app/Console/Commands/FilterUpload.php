<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ProcessTextFilterArchiveUpload;
use Illuminate\Support\Facades\Storage;

class FilterUpload extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'filter:upload {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Text Filter file';

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
     * @return int
     */
    public function handle()
    {
        ProcessTextFilterArchiveUpload::dispatch('default', $this->argument('file'), true);
        return 0;
    }
}
