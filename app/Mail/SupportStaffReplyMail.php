<?php

namespace App\Mail;

use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SupportStaffReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public SupportTicket $ticket,
        public SupportMessage $message,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Support replied: '.$this->ticket->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.support.staff-reply',
            with: [
                'ticketUrl' => route('support.tickets.show', $this->ticket),
            ],
        );
    }
}
