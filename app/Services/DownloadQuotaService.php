<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DownloadQuotaService
{
    public function ensureQuotaAvailable(User $user): void
    {
        $this->resetQuotaIfNeeded($user);

        $limit = $this->monthlyLimitFor($user);

        if ($limit === null) {
            return;
        }

        if ($user->downloads_this_month >= $limit) {
            throw new \RuntimeException($this->quotaExceededMessage($user));
        }
    }

    public function incrementUsage(User $user): void
    {
        $this->resetQuotaIfNeeded($user);
        $user->increment('downloads_this_month');
    }

    public function remaining(User $user): ?int
    {
        $this->resetQuotaIfNeeded($user);

        $limit = $this->monthlyLimitFor($user);

        if ($limit === null) {
            return null;
        }

        return max(0, $limit - $user->downloads_this_month);
    }

    public function monthlyLimitFor(User $user): ?int
    {
        $plan = app(SubscriptionAccessService::class)->effectivePlanFor($user);

        if ($plan?->isUnlimited()) {
            return null;
        }

        return $plan?->monthly_download_limit;
    }

    public function resetQuotaIfNeeded(User $user): void
    {
        $resetAt = $user->quota_reset_at;

        if ($resetAt === null || Carbon::parse($resetAt)->isPast()) {
            $user->forceFill([
                'downloads_this_month' => 0,
                'quota_reset_at' => now()->addMonth()->startOfMonth()->toDateString(),
            ])->save();
        }
    }

    public function quotaExceededMessage(User $user): string
    {
        $this->resetQuotaIfNeeded($user);

        $limit = $this->monthlyLimitFor($user) ?? 0;
        $planName = $user->plan?->name ?? 'Free';
        $resetAt = $user->quota_reset_at?->format('M j, Y');

        $message = "You have used all {$limit} downloads on your {$planName} plan.";

        if ($resetAt !== null) {
            $message .= " Your limit resets on {$resetAt}.";
        }

        if (! Str::contains(strtolower($planName), 'unlimited')) {
            $message .= ' Upgrade plans will be available soon.';
        }

        return $message;
    }
}
