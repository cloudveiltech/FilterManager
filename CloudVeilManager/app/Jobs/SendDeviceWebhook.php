<?php

namespace App\Jobs;

use App\Models\AppUserActivation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Tells cv-manage that a customer's device changed, carrying the device's current row.
 */
class SendDeviceWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** The payload without the fields stamped each time the job runs (`device`, `occurred_at`). */
    public array $payload;

    /**
     * @param  int|null  $activationId  The activation the event is about; its row is sent as `device`.
     * @param  array  $extra  Fields after `event` and `email`, such as identifier, device_id and previous_*.
     * @param  string  $email  The customer the event is about.
     */
    public function __construct(public ?int $activationId, string $event, array $extra, string $email)
    {
        $this->payload = ['event' => $event, 'email' => $email, ...$extra];
    }

    public function backoff(): array
    {
        return [10, 60];
    }

    public function handle(): void
    {
        $url = config('services.cv_manage.device_webhook_url');
        if (blank($url)) {
            return;
        }

        // Read the row on every attempt so a retry never sends stale data.
        $activation = $this->activationId === null
            ? null
            : AppUserActivation::withTrashed()->forManage()->find($this->activationId);

        $response = Http::timeout(5)
            ->acceptJson()
            ->withHeaders(['X-Api-Key' => (string) config('services.cv_manage.device_webhook_secret')])
            ->post($url, [
                ...$this->payload,
                'device' => $activation?->toArray(),
                'occurred_at' => Carbon::now()->toIso8601String(),
            ]);

        if ($response->successful()) {
            return;
        }

        // cv-manage being down or busy is worth retrying; any other rejection will repeat itself.
        if ($response->serverError() || $response->status() === 429) {
            $response->throw();
        }

        $this->fail($response->toException());
    }

    public function failed(?Throwable $exception): void
    {
        Log::warning('Device webhook to cv-manage failed', [
            'payload' => $this->payload,
            'error' => $exception?->getMessage(),
        ]);
    }
}
