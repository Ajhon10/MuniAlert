<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class ReportStatusUpdate extends Mailable
{
    public function __construct(public string $reference, public string $status, public string $url) {}

    public function build(): static
    {
        return $this->subject('MuniAlert report status update')->view('emails.report-status')->text('emails.report-status-text');
    }
}
