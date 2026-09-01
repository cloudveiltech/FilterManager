<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use Log;

class ProcessTextFilterArchiveUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $listNamespace;
    public $file;
    public $shouldOverwrite;
    public $category;
    public $disk;

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
    public function __construct(string $listNamespace, string $file, bool $shouldOverwrite, string $category = '', ?string $disk = null)
    {
        $this->listNamespace = $listNamespace;
        $this->file = $file;
        $this->shouldOverwrite = $shouldOverwrite;
        $this->category = $category;
        $this->disk = $disk;
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

        $file = $this->file;
        $tempFile = null;

        if ($this->disk) {
            $tempFile = $this->copyFromDiskToLocalArchive();
            $file = $tempFile;
        }

        try {
            $flc = new \App\Http\Controllers\FilterListController;
            $flc->processTextFilterArchive($this->listNamespace, $file, $this->shouldOverwrite);
        } finally {
            // processTextFilterArchive() unlinks the archive on success. Clean up
            // after ourselves anyway so a failure part way through does not leave
            // the downloaded copy (or the directory PharData extracts beside it)
            // sitting in storage.
            if (!is_null($tempFile)) {
                $this->cleanUpLocalArchive($tempFile);
            }
        }

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

    /**
     * Streams the archive off of the configured disk into a local file.
     *
     * The local copy must keep its .zip extension: processTextFilterArchive()
     * hands the path to PharData, which refuses any file whose extension it does
     * not recognise. Contents are streamed rather than read into a string, since
     * these archives can be large.
     *
     * @return string The path of the local archive.
     */
    private function copyFromDiskToLocalArchive(): string
    {
        $localDir = storage_path('app/exports');

        if (!is_dir($localDir) && !mkdir($localDir, 0755, true) && !is_dir($localDir)) {
            throw new \RuntimeException('Unable to create export staging directory: ' . $localDir);
        }

        $localArchive = $localDir . '/' . uniqid('export_', true) . '.zip';

        $disk = Storage::disk($this->disk);
        $adapter = $disk->getAdapter();

        if ($adapter instanceof AwsS3Adapter) {
            // Hand the download to the SDK, which sinks the body straight to the
            // given path. Flysystem's readStream() asks Guzzle for a streamed
            // response, and that forces it off the cURL handler onto the PHP
            // stream handler - which does not share cURL's CA trust, so it fails
            // against endpoints such as a local Herd/MinIO with its own CA.
            $adapter->getClient()->getObject([
                'Bucket' => $adapter->getBucket(),
                'Key' => $adapter->applyPathPrefix($this->file),
                'SaveAs' => $localArchive,
            ]);

            Log::info('Copied ' . $this->file . ' from the ' . $this->disk . ' disk to ' . $localArchive
                . ' (' . filesize($localArchive) . ' bytes).');

            return $localArchive;
        }

        $source = $disk->readStream($this->file);

        if (!is_resource($source)) {
            throw new \RuntimeException('Unable to read ' . $this->file . ' from the ' . $this->disk . ' disk.');
        }

        $destination = fopen($localArchive, 'w+b');

        if (!is_resource($destination)) {
            fclose($source);
            throw new \RuntimeException('Unable to open local archive for writing: ' . $localArchive);
        }

        try {
            $written = stream_copy_to_stream($source, $destination);
        } finally {
            fclose($source);
            fclose($destination);
        }

        if ($written === false) {
            @unlink($localArchive);
            throw new \RuntimeException('Failed to copy ' . $this->file . ' from the ' . $this->disk . ' disk.');
        }

        Log::info('Copied ' . $this->file . ' from the ' . $this->disk . ' disk to ' . $localArchive . ' (' . $written . ' bytes).');

        return $localArchive;
    }

    /**
     * Removes the local archive and anything PharData left beside it.
     *
     * @param string $localArchive
     */
    private function cleanUpLocalArchive(string $localArchive)
    {
        if (file_exists($localArchive)) {
            @unlink($localArchive);
        }

        // PharData keeps the opened archive in its cache for the life of the
        // process; unlinking it is enough for a queue worker, but the extracted
        // directory processTextFilterArchive() names is ours to remove.
        $extractedDir = $localArchive . '-dir';

        if (is_dir($extractedDir)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($extractedDir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $fileInfo) {
                $fileInfo->isDir() ? @rmdir($fileInfo->getPathname()) : @unlink($fileInfo->getPathname());
            }

            @rmdir($extractedDir);
        }
    }
}
