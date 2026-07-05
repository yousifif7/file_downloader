<?php

namespace App\Console\Commands;

use App\Models\Plan;
use App\Models\User;
use App\Services\SubscriptionAccessService;
use Illuminate\Console\Command;

class ProcessExpiredSubscriptions extends Command
{
    protected $signature = 'subscriptions:process';

    protected $description = 'Mark overdue bank-transfer subscriptions and downgrade expired accounts to Free';

    public function handle(SubscriptionAccessService $access): int
    {
        $freePlan = $access->freePlan();

        if ($freePlan === null) {
            $this->error('Free plan not found.');

            return self::FAILURE;
        }

        $now = now();
        $pastDue = 0;
        $expired = 0;

        User::query()
            ->with('plan')
            ->whereNotNull('plan_id')
            ->where('billing_provider', config('billing.provider'))
            ->whereIn('subscription_status', [
                User::SUBSCRIPTION_ACTIVE,
                User::SUBSCRIPTION_PAST_DUE,
            ])
            ->chunkById(100, function ($users) use ($access, $freePlan, $now, &$pastDue, &$expired): void {
                foreach ($users as $user) {
                    if ($user->plan?->slug === 'free') {
                        continue;
                    }

                    if ($access->hasHardEnded($user)) {
                        $this->downgradeToFree($user, $freePlan, User::SUBSCRIPTION_EXPIRED);
                        $expired++;

                        continue;
                    }

                    if ($user->subscription_renews_at === null || $user->subscription_renews_at->isFuture()) {
                        continue;
                    }

                    if ($user->subscription_status === User::SUBSCRIPTION_PAST_DUE && $access->isPastGracePeriod($user)) {
                        $this->downgradeToFree($user, $freePlan, User::SUBSCRIPTION_EXPIRED);
                        $expired++;

                        continue;
                    }

                    if ($user->subscription_status === User::SUBSCRIPTION_ACTIVE) {
                        $user->forceFill(['subscription_status' => User::SUBSCRIPTION_PAST_DUE])->save();
                        $pastDue++;
                    }
                }
            });

        User::query()
            ->with('plan')
            ->where('billing_provider', config('billing.complimentary_provider'))
            ->whereNotNull('subscription_ends_at')
            ->where('subscription_ends_at', '<', $now)
            ->chunkById(100, function ($users) use ($freePlan, &$expired): void {
                foreach ($users as $user) {
                    if ($user->plan?->slug === 'free') {
                        continue;
                    }

                    $this->downgradeToFree($user, $freePlan, User::SUBSCRIPTION_EXPIRED);
                    $expired++;
                }
            });

        $this->info("Marked {$pastDue} subscription(s) as past due.");
        $this->info("Downgraded {$expired} subscription(s) to Free.");

        return self::SUCCESS;
    }

    private function downgradeToFree(User $user, Plan $freePlan, string $status): void
    {
        $user->forceFill([
            'plan_id' => $freePlan->id,
            'billing_provider' => null,
            'subscription_status' => $status,
            'subscription_renews_at' => null,
            'subscription_ends_at' => null,
        ])->save();
    }
}
