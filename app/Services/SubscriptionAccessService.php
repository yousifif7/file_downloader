<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\User;
use Carbon\Carbon;

class SubscriptionAccessService
{
    public function effectivePlanFor(User $user): ?Plan
    {
        if (! $this->userHasPaidPlanAccess($user)) {
            return $this->freePlan();
        }

        return $user->relationLoaded('plan') ? $user->plan : $user->plan()->first();
    }

    public function userHasPaidPlanAccess(User $user): bool
    {
        $plan = $user->relationLoaded('plan') ? $user->plan : $user->plan()->first();

        if ($plan === null || $plan->slug === 'free') {
            return true;
        }

        if ($this->hasHardEnded($user)) {
            return false;
        }

        if ($this->isComplimentary($user)) {
            return true;
        }

        if ($user->subscription_status === User::SUBSCRIPTION_EXPIRED) {
            return false;
        }

        if ($user->subscription_status === User::SUBSCRIPTION_CANCELED) {
            return false;
        }

        if ($user->subscription_status === User::SUBSCRIPTION_PAST_DUE) {
            return ! $this->isPastGracePeriod($user);
        }

        return $user->hasActiveSubscription();
    }

    public function isComplimentary(User $user): bool
    {
        return $user->billing_provider === config('billing.complimentary_provider');
    }

    public function hasHardEnded(User $user): bool
    {
        return $user->subscription_ends_at !== null
            && $user->subscription_ends_at->isPast();
    }

    public function isPastGracePeriod(User $user): bool
    {
        if ($user->subscription_renews_at === null) {
            return true;
        }

        return $user->subscription_renews_at
            ->copy()
            ->addDays(config('billing.grace_period_days', 7))
            ->isPast();
    }

    public function graceEndsAt(User $user): ?Carbon
    {
        if ($user->subscription_renews_at === null) {
            return null;
        }

        return $user->subscription_renews_at->copy()->addDays(config('billing.grace_period_days', 7));
    }

    public function freePlan(): ?Plan
    {
        return Plan::query()
            ->where('slug', 'free')
            ->where('is_active', true)
            ->first();
    }
}
