<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class EmailTwoFactorActivation extends Mailable {
    use Queueable, SerializesModels;
    private $link;
    public function __construct($code) {
        $this->code = $code;
    }
    public function build() {
        return $this->subject("Email activation request from CloudVeil")
            ->view('emails.two_factor_activation_request', ["code" => $this->code]);
    }
}
