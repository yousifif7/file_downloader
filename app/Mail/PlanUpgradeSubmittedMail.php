<?php

namespace App\Mail;

use App\Models\PlanUpgradeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PlanUpgradeSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PlanUpgradeRequest $upgradeRequest) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Billing] Upgrade payment submitted — '.$this->upgradeRequest->user?->email,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.billing.upgrade-submitted',
            with: [
                'reviewUrl' => route('admin.upgrade-requests.index', ['status' => 'pending']),
            ],
        );
    }
}
