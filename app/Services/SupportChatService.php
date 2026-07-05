<?php

namespace App\Services;

use App\Mail\SupportStaffReplyMail;
use App\Mail\SupportTicketOpenedMail;
use App\Mail\SupportUserReplyMail;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class SupportChatService
{
    /**
     * @param  array{subject: string, message: string, download_id?: int|null}  $data
     */
    public function openTicket(User $user, array $data): SupportTicket
    {
        return DB::transaction(function () use ($user, $data) {
            $ticket = SupportTicket::query()->create([
                'user_id' => $user->id,
                'subject' => $data['subject'],
                'download_id' => $data['download_id'] ?? null,
                'status' => SupportTicket::STATUS_OPEN,
                'user_last_read_at' => now(),
                'staff_last_read_at' => null,
                'last_message_at' => now(),
                'awaiting_staff' => true,
            ]);

            $message = $this->createMessage($ticket, $user, $data['message'], isStaff: false);

            Mail::to(config('legal.support_email'))->send(new SupportTicketOpenedMail($ticket, $message));

            return $ticket->load(['user', 'messages.user', 'messages.staffUser']);
        });
    }

    public function addUserMessage(SupportTicket $ticket, User $user, string $body): SupportMessage
    {
        $this->assertTicketOwner($ticket, $user);
        $this->assertTicketOpen($ticket);

        return DB::transaction(function () use ($ticket, $user, $body) {
            $message = $this->createMessage($ticket, $user, $body, isStaff: false);

            $ticket->forceFill([
                'status' => SupportTicket::STATUS_OPEN,
                'user_last_read_at' => now(),
                'staff_last_read_at' => null,
                'last_message_at' => now(),
                'awaiting_staff' => true,
            ])->save();

            Mail::to(config('legal.support_email'))->send(new SupportUserReplyMail($ticket, $message));

            return $message->load(['user', 'staffUser']);
        });
    }

    public function addStaffMessage(SupportTicket $ticket, User $staff, string $body): SupportMessage
    {
        return DB::transaction(function () use ($ticket, $staff, $body) {
            $message = $this->createMessage($ticket, $staff, $body, isStaff: true);

            $ticket->forceFill([
                'status' => SupportTicket::STATUS_OPEN,
                'staff_last_read_at' => now(),
                'last_message_at' => now(),
                'awaiting_staff' => false,
            ])->save();

            $ticket->loadMissing('user');

            if ($ticket->user) {
                Mail::to($ticket->user->email)->send(new SupportStaffReplyMail($ticket, $message));
            }

            return $message->load(['user', 'staffUser']);
        });
    }

    public function closeTicket(SupportTicket $ticket): void
    {
        $ticket->forceFill(['status' => SupportTicket::STATUS_CLOSED])->save();
    }

    public function reopenTicket(SupportTicket $ticket): void
    {
        $ticket->forceFill(['status' => SupportTicket::STATUS_OPEN])->save();
    }

    private function createMessage(SupportTicket $ticket, User $author, string $body, bool $isStaff): SupportMessage
    {
        return SupportMessage::query()->create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $isStaff ? null : $author->id,
            'staff_user_id' => $isStaff ? $author->id : null,
            'is_staff' => $isStaff,
            'body' => trim($body),
        ]);
    }

    private function assertTicketOwner(SupportTicket $ticket, User $user): void
    {
        if ($ticket->user_id !== $user->id) {
            abort(403);
        }
    }

    private function assertTicketOpen(SupportTicket $ticket): void
    {
        if ($ticket->isClosed()) {
            throw ValidationException::withMessages([
                'message' => 'This conversation is closed. Please open a new support request.',
            ]);
        }
    }
}
