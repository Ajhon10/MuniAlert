<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class AccountSecurityLink extends Mailable
{
    public function __construct(public string $url, public string $purpose) {}

    public function build(): static
    {
        return $this->subject($this->purpose === 'reset' ? 'MuniAlert password recovery' : 'MuniAlert email verification')
            ->view('emails.security-link-html')->text('emails.security-link');
    }
}
