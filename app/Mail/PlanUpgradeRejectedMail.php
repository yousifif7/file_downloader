<?php

namespace App\Mail;

use App\Models\PlanUpgradeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PlanUpgradeRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PlanUpgradeRequest $upgradeRequest) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Update on your '.$this->upgradeRequest->plan?->name.' upgrade request',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.billing.upgrade-rejected',
            with: [
                'upgradeUrl' => route('upgrade.index'),
                'supportUrl' => route('support.tickets.create'),
            ],
        );
    }
}
