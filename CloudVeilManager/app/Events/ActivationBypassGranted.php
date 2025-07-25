<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

use App\Models\AppUserActivation;

class ActivationBypassGranted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $activationBypass;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(AppUserActivation $activationBypass)
    {
        //
        $this->activationBypass = $activationBypass;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('bypass_granted');
    }
}
