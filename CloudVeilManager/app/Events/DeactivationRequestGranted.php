<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Models\DeactivationRequest;

class DeactivationRequestGranted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $deactivationRequest;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(DeactivationRequest $deactivationRequest)
    {
        $this->deactivationRequest = $deactivationRequest;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
