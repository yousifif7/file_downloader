<?php

namespace App\Mail;

use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SupportTicketOpenedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public SupportTicket $ticket,
        public SupportMessage $message,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Support] New conversation #'.$this->ticket->id.': '.$this->ticket->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.support.ticket-opened',
            with: [
                'ticketUrl' => route('admin.support-tickets.show', $this->ticket),
            ],
        );
    }
}
