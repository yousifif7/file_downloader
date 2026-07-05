<?php

namespace App\Mail;

use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SupportUserReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public SupportTicket $ticket,
        public SupportMessage $message,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Support] Customer replied on #'.$this->ticket->id,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.support.user-reply',
            with: [
                'ticketUrl' => route('admin.support-tickets.show', $this->ticket),
            ],
        );
    }
}
