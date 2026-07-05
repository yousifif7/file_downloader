<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Platform;
use App\Models\User;

class PlanPlatformAccessService
{
    public function userCanAccessPlatform(User $user, ?string $slug): bool
    {
        if ($slug === null || $slug === '') {
            return false;
        }

        $platform = Platform::query()
            ->where('slug', $slug)
            ->where('is_enabled', true)
            ->first();

        if ($platform === null) {
            return false;
        }

        $plan = $this->resolvePlanFor($user);

        if ($plan === null) {
            return false;
        }

        return $plan->platforms()
            ->where('platforms.id', $platform->id)
            ->exists();
    }

    public function denialMessage(User $user, string $slug): string
    {
        $platformName = Platform::query()->where('slug', $slug)->value('name') ?? 'This platform';
        $planName = $this->resolvePlanFor($user)?->name ?? 'your plan';

        return "{$platformName} is not included on {$planName}. Upgrade your plan to download from more platforms.";
    }

    private function resolvePlanFor(User $user): ?Plan
    {
        return app(SubscriptionAccessService::class)->effectivePlanFor($user);
    }
}
