<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\PlanUpgradeRequest;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_shows_pending_work_alerts(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();

        $plan = Plan::query()->create([
            'name' => 'Pro',
            'slug' => 'pro',
            'monthly_download_limit' => 50,
            'price_cents' => 250,
            'is_active' => true,
        ]);

        PlanUpgradeRequest::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => PlanUpgradeRequest::STATUS_PENDING,
            'payment_reference' => 'DL-1-PRO',
        ]);

        SupportTicket::query()->create([
            'user_id' => $user->id,
            'subject' => 'Need help with billing',
            'status' => SupportTicket::STATUS_OPEN,
            'awaiting_staff' => true,
            'last_message_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Needs your attention')
            ->assertSee('1 pending bank transfer')
            ->assertSee('1 support ticket awaiting reply')
            ->assertSee('Need help with billing');
    }

    public function test_admin_login_redirects_to_admin_dashboard(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'email' => 'admin@example.com',
        ]);

        $this->post(route('login'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));
    }
}
