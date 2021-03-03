<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class EmailActivation extends Mailable {
    use Queueable, SerializesModels;

    private $link;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($link) {
        $this->link = $link;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build() {
        return $this->subject("Email activation request from " . config('app.company_name'))
            ->view('emails.activation_request', ["link" => $this->link]);
    }
}
