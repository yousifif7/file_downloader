<?php

namespace Tests\Unit;

use App\Models\Plan;
use App\Models\User;
use App\Services\SubscriptionAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_complimentary_user_keeps_paid_plan_access(): void
    {
        $plan = Plan::query()->create([
            'name' => 'Unlimited',
            'slug' => 'unlimited',
            'monthly_download_limit' => null,
            'price_cents' => null,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'plan_id' => $plan->id,
            'billing_provider' => 'complimentary',
            'subscription_status' => User::SUBSCRIPTION_ACTIVE,
        ]);

        $access = app(SubscriptionAccessService::class);

        $this->assertTrue($access->userHasPaidPlanAccess($user));
        $this->assertSame($plan->id, $access->effectivePlanFor($user)?->id);
    }

    public function test_expired_paid_user_falls_back_to_free_plan(): void
    {
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
            'price_cents' => 999,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'plan_id' => $pro->id,
            'billing_provider' => 'bank_transfer',
            'subscription_status' => User::SUBSCRIPTION_EXPIRED,
        ]);

        $access = app(SubscriptionAccessService::class);

        $this->assertFalse($access->userHasPaidPlanAccess($user));
        $this->assertSame($free->id, $access->effectivePlanFor($user)?->id);
    }
}
