<?php

namespace App\Mail;

use App\Models\DeactivationRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DeactivationRequestGrantedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The deactivation request instance.
     *
     * @var DeactivationRequest
     */
    public $deactivationRequest;

    /**
     * The user instance.
     *
     * @var User
     */
    public $user;

    /**
     * The platform name.
     *
     * @var string
     */
    public $platform;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(DeactivationRequest $deactivationRequest, User $user, $platform)
    {
        $this->deactivationRequest = $deactivationRequest;
        $this->user = $user;
        $this->platform = $platform;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.deactivation_request_granted');
    }
}
