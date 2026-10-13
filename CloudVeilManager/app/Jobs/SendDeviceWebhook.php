<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Tells cv-manage that a customer's devices changed so it can refresh its device list.
 */
class SendDeviceWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    public function __construct(public array $payload) {}

    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function handle(): void
    {
        $url = config('services.cv_manage.device_webhook_url');
        if (blank($url)) {
            return;
        }

        Http::timeout(5)
            ->acceptJson()
            ->withHeaders(['X-Api-Key' => (string) config('services.cv_manage.device_webhook_secret')])
            ->post($url, $this->payload)
            ->throw();
    }

    public function failed(?Throwable $exception): void
    {
        Log::warning('Device webhook to cv-manage failed', [
            'payload' => $this->payload,
            'error' => $exception?->getMessage(),
        ]);
    }
}
