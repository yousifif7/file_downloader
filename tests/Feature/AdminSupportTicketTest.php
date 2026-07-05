<?php

namespace Tests\Feature;

use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSupportTicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_support_chat(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();

        $ticket = SupportTicket::query()->create([
            'user_id' => $user->id,
            'subject' => 'Refund request',
            'status' => SupportTicket::STATUS_OPEN,
            'awaiting_staff' => true,
            'last_message_at' => now(),
        ]);

        SupportMessage::query()->create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'is_staff' => false,
            'body' => 'Please help with my subscription refund.',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.support-tickets.index'))
            ->assertOk()
            ->assertSee('Refund request')
            ->assertSee($user->email);

        $this->actingAs($admin)
            ->get(route('admin.support-tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Please help with my subscription refund.')
            ->assertSee('Reply to customer');

        $this->assertNotNull($ticket->fresh()->staff_last_read_at);
        $this->assertTrue($ticket->fresh()->awaiting_staff);
    }

    public function test_non_admin_cannot_view_support_tickets(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('admin.support-tickets.index'))
            ->assertForbidden();
    }
}
