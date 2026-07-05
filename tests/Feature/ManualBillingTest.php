<?php

namespace Tests\Feature;

use App\Mail\PlanUpgradeApprovedMail;
use App\Mail\PlanUpgradeRejectedMail;
use App\Mail\PlanUpgradeSubmittedMail;
use App\Models\Plan;
use App\Models\PlanUpgradeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
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

        $response = $this->actingAs($user)->post(route('upgrade.bank.store', $plan), [
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
            'payment_method' => PlanUpgradeRequest::PAYMENT_METHOD_BANK,
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
            'payment_method' => PlanUpgradeRequest::PAYMENT_METHOD_BANK,
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
            'payment_method' => PlanUpgradeRequest::PAYMENT_METHOD_BANK,
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

    public function test_upgrade_show_displays_payment_method_options_when_crypto_enabled(): void
    {
        config([
            'billing.iban' => 'PS00BOPX00000000000000000000',
            'billing.crypto.enabled' => true,
            'billing.crypto.api_key' => 'test-plisio-key',
        ]);

        $user = User::factory()->create();
        $plan = $this->paidPlan();

        $this->actingAs($user)
            ->get(route('upgrade.show', $plan))
            ->assertOk()
            ->assertSee('Choose how to pay')
            ->assertSee('Bank transfer')
            ->assertSee('Crypto (USDT)')
            ->assertSee('Instant');
    }

    public function test_user_can_start_crypto_checkout_and_redirect_to_plisio(): void
    {
        config([
            'billing.crypto.enabled' => true,
            'billing.crypto.api_key' => 'test-plisio-key',
        ]);

        Http::fake([
            'api.plisio.net/*' => Http::response([
                'status' => 'success',
                'data' => [
                    'txn_id' => 'plisio-txn-123',
                    'invoice_url' => 'https://plisio.net/invoice/plisio-txn-123',
                ],
            ]),
        ]);

        $user = User::factory()->create();
        $plan = $this->paidPlan();

        $response = $this->actingAs($user)->post(route('upgrade.crypto.store', $plan));

        $response->assertRedirect('https://plisio.net/invoice/plisio-txn-123');

        $this->assertDatabaseHas('plan_upgrade_requests', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => PlanUpgradeRequest::STATUS_PENDING,
            'payment_method' => PlanUpgradeRequest::PAYMENT_METHOD_CRYPTO,
            'provider_payment_id' => 'plisio-txn-123',
        ]);
    }

    public function test_plisio_webhook_activates_plan_on_completed_payment(): void
    {
        Mail::fake();

        config([
            'billing.crypto.enabled' => true,
            'billing.crypto.api_key' => 'test-plisio-key',
        ]);

        $user = User::factory()->create();
        $plan = $this->paidPlan();

        $upgradeRequest = PlanUpgradeRequest::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => PlanUpgradeRequest::STATUS_PENDING,
            'payment_method' => PlanUpgradeRequest::PAYMENT_METHOD_CRYPTO,
            'provider' => 'plisio',
            'provider_payment_id' => 'plisio-txn-456',
            'payment_reference' => 'DL-'.$user->id.'-PRO',
        ]);

        $payload = [
            'txn_id' => 'plisio-txn-456',
            'order_number' => (string) $upgradeRequest->id,
            'status' => 'completed',
            'amount' => '9.99',
            'currency' => 'USDT',
        ];

        $payload['verify_hash'] = app(\App\Services\PlisioBillingService::class)
            ->callbackSignature($payload, 'test-plisio-key');

        $this->postJson(route('webhooks.plisio', ['json' => 'true']), $payload)
            ->assertOk();

        $user->refresh();
        $upgradeRequest->refresh();

        $this->assertSame($plan->id, $user->plan_id);
        $this->assertSame(User::SUBSCRIPTION_ACTIVE, $user->subscription_status);
        $this->assertSame('plisio', $user->billing_provider);
        $this->assertSame(PlanUpgradeRequest::STATUS_APPROVED, $upgradeRequest->status);

        Mail::assertSent(PlanUpgradeApprovedMail::class, fn ($mail) => $mail->hasTo($user->email));
    }

    public function test_plisio_webhook_rejects_invalid_signature(): void
    {
        config([
            'billing.crypto.enabled' => true,
            'billing.crypto.api_key' => 'test-plisio-key',
        ]);

        $this->postJson(route('webhooks.plisio', ['json' => 'true']), [
            'status' => 'completed',
            'order_number' => '1',
            'verify_hash' => 'invalid',
        ])->assertStatus(422);
    }

    public function test_plisio_webhook_cancels_request_on_expired_payment(): void
    {
        config([
            'billing.crypto.enabled' => true,
            'billing.crypto.api_key' => 'test-plisio-key',
        ]);

        $user = User::factory()->create();
        $plan = $this->paidPlan();

        $upgradeRequest = PlanUpgradeRequest::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => PlanUpgradeRequest::STATUS_PENDING,
            'payment_method' => PlanUpgradeRequest::PAYMENT_METHOD_CRYPTO,
            'provider' => 'plisio',
            'provider_payment_id' => 'plisio-txn-expired',
            'payment_reference' => 'DL-'.$user->id.'-PRO',
        ]);

        $payload = [
            'txn_id' => 'plisio-txn-expired',
            'order_number' => (string) $upgradeRequest->id,
            'status' => 'expired',
        ];

        $payload['verify_hash'] = app(\App\Services\PlisioBillingService::class)
            ->callbackSignature($payload, 'test-plisio-key');

        $this->postJson(route('webhooks.plisio', ['json' => 'true']), $payload)
            ->assertOk();

        $upgradeRequest->refresh();

        $this->assertSame(PlanUpgradeRequest::STATUS_CANCELLED, $upgradeRequest->status);
    }

    public function test_stale_crypto_requests_are_auto_cancelled(): void
    {
        config([
            'billing.crypto.invoice_expire_minutes' => 60,
        ]);

        $user = User::factory()->create();
        $plan = $this->paidPlan();

        $upgradeRequest = PlanUpgradeRequest::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => PlanUpgradeRequest::STATUS_PENDING,
            'payment_method' => PlanUpgradeRequest::PAYMENT_METHOD_CRYPTO,
            'payment_reference' => 'DL-'.$user->id.'-PRO',
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ]);

        $expired = app(\App\Services\PlisioBillingService::class)->expireStaleCryptoRequests();

        $this->assertSame(1, $expired);
        $this->assertSame(PlanUpgradeRequest::STATUS_CANCELLED, $upgradeRequest->fresh()->status);
    }

    public function test_admin_dashboard_ignores_pending_crypto_for_bank_transfer_alert(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();

        $plan = Plan::query()->create([
            'name' => 'Ultimate',
            'slug' => 'ultimate',
            'monthly_download_limit' => 100,
            'price_cents' => 699,
            'is_active' => true,
        ]);

        PlanUpgradeRequest::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => PlanUpgradeRequest::STATUS_PENDING,
            'payment_method' => PlanUpgradeRequest::PAYMENT_METHOD_CRYPTO,
            'payment_reference' => 'DL-1-ULTIMATE',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('pending bank transfer');
    }
}
