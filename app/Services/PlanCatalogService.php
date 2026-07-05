<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Platform;
use Illuminate\Database\Eloquent\Collection;

class PlanCatalogService
{
    public function freePlan(): ?Plan
    {
        return Plan::query()
            ->with(['platforms' => fn ($query) => $query->orderBy('name')])
            ->where('slug', 'free')
            ->where('is_active', true)
            ->first();
    }

    /**
     * @return Collection<int, Plan>
     */
    public function activePlans(): Collection
    {
        return Plan::query()
            ->with(['platforms' => fn ($query) => $query->orderBy('name')])
            ->where('is_active', true)
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  Collection<int, Plan>  $plans
     */
    public function recommendedPlan(?Collection $plans = null): ?Plan
    {
        $plans ??= $this->activePlans();
        $slug = config('billing.recommended_plan_slug', 'pro');

        $match = $plans->first(fn (Plan $plan) => $plan->slug === $slug && $plan->price_cents > 0);

        if ($match !== null) {
            return $match;
        }

        return $plans
            ->filter(fn (Plan $plan) => $plan->price_cents > 0)
            ->sortBy('price_cents')
            ->first();
    }

    /**
     * @param  Collection<int, Plan>  $plans
     */
    public function isRecommendedPlan(Plan $plan, Collection $plans, ?Plan $currentPlan = null): bool
    {
        if ($currentPlan && $currentPlan->id === $plan->id) {
            return false;
        }

        $recommended = $this->recommendedPlan($plans);

        return $recommended !== null && $recommended->id === $plan->id;
    }

    /**
     * @param  Collection<int, Plan>  $plans
     */
    public function isTopTier(Plan $plan, ?Collection $plans = null): bool
    {
        if (! $plan->price_cents) {
            return false;
        }

        $plans ??= $this->activePlans();
        $paidPlans = $plans->filter(fn (Plan $plan) => $plan->price_cents > 0);

        if ($paidPlans->count() < 2) {
            return false;
        }

        $maxPrice = $paidPlans->max('price_cents');

        return $maxPrice > 0 && $plan->price_cents === $maxPrice;
    }

    public function monthlyLimitLabel(?Plan $plan): string
    {
        if ($plan === null) {
            return 'Limited downloads / month';
        }

        if ($plan->isUnlimited()) {
            return 'Unlimited downloads';
        }

        $limit = $plan->monthly_download_limit;

        if ($limit === null) {
            return 'Limited downloads / month';
        }

        return "{$limit} downloads / month";
    }

    public function monthlyLimitShort(?Plan $plan): string
    {
        if ($plan === null || $plan->monthly_download_limit === null) {
            return $plan?->isUnlimited() ? 'Unlimited' : 'Limited';
        }

        return (string) $plan->monthly_download_limit;
    }

    public function platformNamesLabel(?Plan $plan): string
    {
        if ($plan === null || $plan->platforms->isEmpty()) {
            return 'Supported platforms';
        }

        return $this->formatNameList($plan->platforms->pluck('name')->all());
    }

    /**
     * @param  Collection<int, Platform>  $platforms
     */
    public function enabledPlatformNamesLabel(Collection $platforms): string
    {
        $names = $platforms
            ->where('is_enabled', true)
            ->sortBy('name')
            ->pluck('name')
            ->all();

        return $this->formatNameList($names);
    }

    /**
     * @param  Collection<int, Plan>|null  $plans
     * @return array{status: 'coming_soon'|'free'|'paid', plan: ?Plan}
     */
    public function platformAvailability(Platform $platform, ?Plan $freePlan = null, ?Collection $plans = null): array
    {
        if (! $platform->is_enabled) {
            return ['status' => 'coming_soon', 'plan' => null];
        }

        $freePlan ??= $this->freePlan();
        $plans ??= $this->activePlans();

        if ($freePlan !== null && $freePlan->platforms->contains('id', $platform->id)) {
            return ['status' => 'free', 'plan' => $freePlan];
        }

        $paidPlan = $plans
            ->filter(fn (Plan $plan) => $plan->price_cents > 0)
            ->filter(fn (Plan $plan) => $plan->platforms->contains('id', $platform->id))
            ->sortBy('price_cents')
            ->first();

        if ($paidPlan !== null) {
            return ['status' => 'paid', 'plan' => $paidPlan];
        }

        return ['status' => 'coming_soon', 'plan' => null];
    }

    /**
     * @return list<array{text: string, detail: string|null}>
     */
    public function featureBullets(?Plan $plan, ?Collection $allPlans = null): array
    {
        if ($plan === null) {
            return [];
        }

        $allPlans ??= $this->activePlans();

        $features = [
            ['text' => $this->monthlyLimitLabel($plan), 'detail' => null],
            $this->platformFeature($plan),
            ['text' => 'Download history & account dashboard', 'detail' => null],
        ];

        if ($plan->price_cents > 0) {
            $features[] = $this->isTopTier($plan, $allPlans)
                ? ['text' => 'Priority email support', 'detail' => 'Faster responses on business days']
                : ['text' => 'Email support', 'detail' => null];
        }

        return $features;
    }

    /**
     * @return array{text: string, detail: string|null}
     */
    private function platformFeature(Plan $plan): array
    {
        $count = $plan->platforms->count();

        if ($count === 0) {
            return ['text' => 'Supported platforms', 'detail' => null];
        }

        $names = $plan->platforms->pluck('name')->all();

        if ($count <= 4) {
            return ['text' => $this->formatNameList($names), 'detail' => null];
        }

        return [
            'text' => "All {$count} supported platforms",
            'detail' => $this->formatNameList($names),
        ];
    }

    /**
     * @param  list<string>  $names
     */
    private function formatNameList(array $names): string
    {
        if ($names === []) {
            return 'Supported platforms';
        }

        if (count($names) === 1) {
            return $names[0];
        }

        $last = array_pop($names);

        return implode(', ', $names).' & '.$last;
    }

    public function priceLabel(?Plan $plan): string
    {
        if ($plan === null || $plan->price_cents === null || $plan->price_cents === 0) {
            return '$0';
        }

        return '$'.number_format($plan->price_cents / 100, 2);
    }

    public function seoDescription(?Plan $freePlan = null): string
    {
        $freePlan ??= $this->freePlan();
        $limit = $this->monthlyLimitShort($freePlan);
        $platforms = $this->platformNamesLabel($freePlan);

        return "Download videos from {$platforms}. Paste a URL, choose MP4 or audio quality, and save to your device. Free account — {$limit} downloads per month.";
    }
}
