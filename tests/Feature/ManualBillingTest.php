<?php

namespace Tests\Feature;

use App\Mail\PlanUpgradeApprovedMail;
use App\Mail\PlanUpgradeRejectedMail;
use App\Mail\PlanUpgradeSubmittedMail;
use App\Mail\WelcomeMail;
use App\Models\Plan;
use App\Models\PlanUpgradeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ManualBillingTest extends TestCase
{
    use RefreshDatabase;

    private function paidPlan(): Plan
    {
        return Plan::query()->create([
            'name' => 'Pro',
            'slug' => 'pro',
            'monthly_download_limit' => 50,
            'price_cents' => 999,
            'is_active' => true,
        ]);
    }

    public function test_user_can_submit_bank_transfer_upgrade_request(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $plan = $this->paidPlan();

        $response = $this->actingAs($user)->post(route('upgrade.store', $plan), [
            'payment_reference' => 'DL-'.$user->id.'-PRO',
            'payer_note' => 'Sent today via Bank of Palestine',
            'receipt' => UploadedFile::fake()->image('receipt.jpg'),
            'confirmed' => '1',
        ]);

        $response->assertRedirect(route('upgrade.index'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('plan_upgrade_requests', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => PlanUpgradeRequest::STATUS_PENDING,
        ]);

        $stored = PlanUpgradeRequest::query()->first();
        $this->assertNotNull($stored->receipt_path);
        $this->assertFileExists(public_path($stored->receipt_path));

        Mail::assertSent(PlanUpgradeSubmittedMail::class);
    }

    public function test_admin_can_approve_upgrade_and_activate_plan(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $plan = $this->paidPlan();

        $upgradeRequest = PlanUpgradeRequest::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => PlanUpgradeRequest::STATUS_PENDING,
            'payment_reference' => 'DL-'.$user->id.'-PRO',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.upgrade-requests.approve', $upgradeRequest))
            ->assertRedirect();

        $user->refresh();

        $this->assertSame($plan->id, $user->plan_id);
        $this->assertSame(User::SUBSCRIPTION_ACTIVE, $user->subscription_status);
        $this->assertSame('bank_transfer', $user->billing_provider);
        $this->assertNotNull($user->subscription_renews_at);

        Mail::assertSent(PlanUpgradeApprovedMail::class, fn ($mail) => $mail->hasTo($user->email));
    }

    public function test_admin_can_reject_upgrade_and_notify_user(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $plan = $this->paidPlan();

        $upgradeRequest = PlanUpgradeRequest::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => PlanUpgradeRequest::STATUS_PENDING,
            'payment_reference' => 'DL-'.$user->id.'-PRO',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.upgrade-requests.reject', $upgradeRequest), [
                'admin_note' => 'Receipt amount does not match the plan price.',
            ])
            ->assertRedirect();

        $upgradeRequest->refresh();

        $this->assertSame(PlanUpgradeRequest::STATUS_REJECTED, $upgradeRequest->status);
        $this->assertSame('Receipt amount does not match the plan price.', $upgradeRequest->admin_note);

        Mail::assertSent(PlanUpgradeRejectedMail::class, fn ($mail) => $mail->hasTo($user->email));

        $this->actingAs($user)
            ->get(route('account'))
            ->assertOk()
            ->assertSee('Upgrade not approved')
            ->assertSee('Receipt amount does not match the plan price.');

        $this->actingAs($user)
            ->post(route('account.upgrade-requests.dismiss', $upgradeRequest))
            ->assertRedirect();

        $this->actingAs($user)
            ->get(route('account'))
            ->assertOk()
            ->assertDontSee('Receipt amount does not match the plan price.');
    }

    public function test_guest_cannot_access_upgrade_pages(): void
    {
        $plan = $this->paidPlan();

        $this->get(route('upgrade.index'))->assertRedirect(route('login'));
        $this->get(route('upgrade.show', $plan))->assertRedirect(route('login'));
    }
}
