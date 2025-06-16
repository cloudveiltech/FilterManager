<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class ProcessTextFilterArchiveUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $listNamespace;
    public $file;
    public $shouldOverwrite;
    public $category;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 1700;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $listNamespace, string $file, bool $shouldOverwrite, string $category = '')
    {
        $this->listNamespace = $listNamespace;
        $this->file = $file;
        $this->shouldOverwrite = $shouldOverwrite;
        $this->category = $category;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('Running processTextFilterArchive Job.');
        $client = new Client();

        try {
            $payload = json_encode(
                [
                    'channel' => config('services.slack.channel.import'),
                    'text' => "Beginning " . $this->category . " File Import. File: " . $this->file . " Should Overwrite: " . $this->shouldOverwrite . " List: " . $this->listNamespace,
                    'username' => config('app.name')
                ]);

            $res = $client->request('POST', config('services.slack.url'),
                [
                    'body' => $payload
                ]
            );
        } catch (\Exception $e) {
            Log::error($e);
        }

        $flc = new \App\Http\Controllers\FilterListController;
        $flc->processTextFilterArchive($this->listNamespace, $this->file, $this->shouldOverwrite);

        Log::info('Finished processTextFilterArchive Job.');

        try {
            $payload = json_encode(
                [
                    'channel' => config('services.slack.channel.import'),
                    'text' => "Completed " . $this->category . " File Import. File: " . $this->file . " Should Overwrite: " . $this->shouldOverwrite . " List: " . $this->listNamespace,
                    'username' => config('app.name')
                ]);

            $res = $client->request('POST', config('services.slack.url'),
                [
                    'body' => $payload
                ]
            );
        } catch (\Exception $e) {
            Log::error($e);
        }

    }
}
