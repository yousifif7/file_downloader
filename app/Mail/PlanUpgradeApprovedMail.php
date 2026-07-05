<?php

namespace App\Mail;

use App\Models\PlanUpgradeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PlanUpgradeApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PlanUpgradeRequest $upgradeRequest) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your '.$this->upgradeRequest->plan?->name.' plan is now active',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.billing.upgrade-approved',
            with: [
                'accountUrl' => route('account'),
            ],
        );
    }
}
