<?php

namespace Tests\Feature;

use App\Mail\SupportStaffReplyMail;
use App\Mail\SupportTicketOpenedMail;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SupportChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_support_landing_with_login_prompt(): void
    {
        $this->get(route('support'))
            ->assertOk()
            ->assertSee('Log in to contact support');
    }

    public function test_authenticated_user_is_redirected_to_ticket_inbox(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('support'))
            ->assertRedirect(route('support.tickets.index'));
    }

    public function test_user_can_open_support_conversation(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('support.tickets.store'), [
            'subject' => 'Download failed',
            'download_id' => 7,
            'message' => 'My YouTube download keeps failing on a supported link.',
        ]);

        $ticket = SupportTicket::query()->first();

        $response->assertRedirect(route('support.tickets.show', $ticket));

        $this->assertDatabaseHas('support_tickets', [
            'id' => $ticket->id,
            'user_id' => $user->id,
            'subject' => 'Download failed',
            'awaiting_staff' => true,
        ]);

        $this->assertDatabaseHas('support_messages', [
            'support_ticket_id' => $ticket->id,
            'is_staff' => false,
        ]);

        Mail::assertSent(SupportTicketOpenedMail::class);
    }

    public function test_admin_reply_is_visible_to_user_and_emails_user(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);

        $ticket = SupportTicket::query()->create([
            'user_id' => $user->id,
            'subject' => 'Billing or subscription',
            'status' => SupportTicket::STATUS_OPEN,
            'awaiting_staff' => true,
            'last_message_at' => now(),
        ]);

        SupportMessage::query()->create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'is_staff' => false,
            'body' => 'I need help with my plan.',
        ]);

        $this->actingAs($admin)->post(route('admin.support-tickets.messages.store', $ticket), [
            'message' => 'Happy to help — what plan are you on?',
        ])->assertRedirect(route('admin.support-tickets.show', $ticket));

        $this->actingAs($user)
            ->get(route('support.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Happy to help — what plan are you on?');

        Mail::assertSent(SupportStaffReplyMail::class, function (SupportStaffReplyMail $mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_guest_cannot_access_support_chat_routes(): void
    {
        $this->get(route('support.tickets.index'))->assertRedirect(route('login'));
        $this->post(route('support.tickets.store'))->assertRedirect(route('login'));
    }
}
