<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use App\Services\ManualBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPlanGrantTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_grant_complimentary_unlimited_access(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();

        $unlimited = Plan::query()->create([
            'name' => 'Unlimited',
            'slug' => 'unlimited',
            'monthly_download_limit' => null,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.grant-complimentary', $user), [
                'plan_id' => $unlimited->id,
            ])
            ->assertRedirect();

        $user->refresh();

        $this->assertSame($unlimited->id, $user->plan_id);
        $this->assertSame('complimentary', $user->billing_provider);
        $this->assertTrue($user->isComplimentary());
    }

    public function test_admin_can_revoke_user_to_free(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $free = Plan::query()->create([
            'name' => 'Free',
            'slug' => 'free',
            'monthly_download_limit' => 3,
            'is_active' => true,
        ]);

        $pro = Plan::query()->create([
            'name' => 'Pro',
            'slug' => 'pro',
            'monthly_download_limit' => 50,
            'is_active' => true,
        ]);

        $user = User::factory()->create(['plan_id' => $pro->id]);

        app(ManualBillingService::class)->grantComplimentary($user, $pro);

        $this->actingAs($admin)
            ->post(route('admin.users.revoke-plan', $user))
            ->assertRedirect();

        $user->refresh();

        $this->assertSame($free->id, $user->plan_id);
        $this->assertNull($user->billing_provider);
    }
}
