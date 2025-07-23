<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

use App\Models\User;

class AccountabilityPartnerEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    /**
     * The user instance.
     *
     * @var User
     */
    public $user;

    /**
     * The data type instance.
     *
     * @var Action
     */
    public $data_type;

    /**
     * The url instance.
     *
     * @var Title
     */
    public $url;

    /**
     * The source_name instance.
     *
     * @var source_name
     */
    public $source_name;

    /**
     * The message instance.
     *
     * @var message
     */
    public $message;

    /*

    /*
     * Initially, incoming data will be prioritized as follows:
     *
     * Level 1 - This is data that is of the utmost importance and
     * shall be forwarded to the account holder and partners immediately.
     * An example is a deactivation request for CV4W.
     * I’d also treat a hit on an actual porn site as a level 1.
     * That should not include sites like Google images, Pinterest,
     * or Etsy as it will lead to way too many false positives making it
     * useless.  It should also not trigger on content alerts unless
     * we’re 100% certain.
     *
     * Level 2 - This is data that needs to go to the partner but can
     * go through on the next daily sweep.  I’m thinking of CloudLocker
     * and cv4w license alerts or devices being offline for too long.
     *
     * Level 3 - This is data that just needs to be logged.  I don’t
     * have any example right now.  Maybe we don’t need this one.
     */
    public $severity;

    /*
     * 1-10
     * These are not defined very well.  The intent is to determine on a scale
     * from 1-10 how accurate this complaint is.  A deactivation request
     * or a hit on an adult would be a 10 but content filtering triggering
     * would probably only be a 6 or 7.
     */
    public $certainty;

    /**
     * AccountabilityPartnerEvent constructor.
     * @param User $user
     * @param String $data_type
     * @param String $url
     * @param String $source_name
     * @param int $severity
     * @param int $certainty
     */
    public function __construct(User $user, String $data_type, String $source_name,
                                String $url, String $message, int $severity, int $certainty)
    {
        $this->user = $user;
        $this->data_type = $data_type;
        $this->source_name = $source_name;
        $this->url = $url;
        $this->message = $message;
        $this->severity = $severity;
        $this->certainty = $certainty;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
     //   return new PrivateChannel('channel-name');
    }
}
