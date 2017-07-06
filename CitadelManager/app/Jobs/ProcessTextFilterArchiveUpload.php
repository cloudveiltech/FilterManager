<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessTextFilterArchiveUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $listNamespace;
    public $file;
    public $shouldOverwrite;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $listNamespace, string $file, bool $shouldOverwrite )
    {
        $this->listNamespace = $listNamespace;
        $this->file = $file;
        $this->shouldOverwrite = $shouldOverwrite;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
      $flc = new \App\Http\Controllers\FilterListController;
      $flc->processTextFilterArchive($event->listNamespace, $event->file, $event->shouldOverwrite);
    }
}
