@props([
    'plan',
    'catalog',
    'plans',
    'currentPlan' => null,
    'upgradeMode' => false,
])

@php
    $isCurrent = $currentPlan && $currentPlan->id === $plan->id;
    $isRecommended = $catalog->isRecommendedPlan($plan, $plans, $currentPlan);
    $isHighlighted = $isCurrent || $isRecommended;
    $isPlaceholder = $plan->slug !== 'free' && ! $plan->price_cents;
@endphp

<div @class([
    'relative flex flex-col rounded-2xl border bg-slate-900/60 backdrop-blur-xl p-6 sm:p-7 transition',
    'border-violet-500/60 ring-2 ring-violet-500/25 shadow-xl shadow-violet-500/10 lg:scale-[1.02] z-10' => $isRecommended && ! $isCurrent,
    'border-violet-500/40 ring-1 ring-violet-500/20' => $isCurrent,
    'border-slate-800/80' => ! $isHighlighted,
    'opacity-60' => $isPlaceholder,
])>
    @if ($isCurrent)
        <div class="absolute -top-4 left-1/2 -translate-x-1/2 rounded-full bg-violet-600 px-5 py-1.5 text-xs font-semibold text-white shadow-lg shadow-violet-500/30 whitespace-nowrap">
            Current plan
        </div>
    @elseif ($isRecommended)
        <div class="absolute -top-4 left-1/2 -translate-x-1/2 rounded-full bg-gradient-to-r from-violet-600 to-fuchsia-600 px-5 py-1.5 text-xs font-semibold text-white shadow-lg shadow-violet-500/30 whitespace-nowrap">
            Most popular
        </div>
    @endif

    <div @class(['mb-5', 'pt-3' => $isCurrent || $isRecommended])>
        <h3 class="text-lg font-semibold text-white">{{ $plan->name }}</h3>
        <p class="mt-3 flex items-baseline gap-1">
            @if ($plan->price_cents)
                <span class="text-4xl font-bold tracking-tight text-white">{{ $catalog->priceLabel($plan) }}</span>
                <span class="text-sm font-medium text-slate-500">/month</span>
            @else
                <span class="text-4xl font-bold tracking-tight text-white">{{ $plan->slug === 'free' ? '$0' : 'Soon' }}</span>
                @if ($plan->slug === 'free')
                    <span class="text-sm font-medium text-slate-500">/month</span>
                @endif
            @endif
        </p>
        @if ($plan->slug === 'free')
            <p class="mt-1.5 text-xs text-slate-500">No credit card required</p>
        @elseif ($plan->price_cents)
            <p class="mt-1.5 text-xs text-slate-500">Billed monthly · bank or crypto</p>
        @endif
    </div>

    @include('partials.plan-features', [
        'plan' => $plan,
        'catalog' => $catalog,
        'plans' => $plans,
        'class' => 'flex-1',
    ])

    <div class="mt-8 pt-6 border-t border-slate-800/80">
        @if ($isCurrent)
            <span class="btn-secondary w-full justify-center opacity-60 cursor-default">Current plan</span>
        @elseif ($plan->slug === 'free')
            <a href="{{ auth()->check() ? route('account') : route('register') }}" @class([
                'w-full',
                'btn-primary' => ! $isRecommended,
                'btn-secondary' => $isRecommended,
            ])>Get started free</a>
        @elseif ($plan->price_cents)
            <a href="{{ auth()->check() ? route('upgrade.show', $plan) : route('register') }}" @class([
                'w-full justify-center',
                'btn-primary' => $isRecommended,
                'btn-secondary' => ! $isRecommended,
            ])>
                @if ($upgradeMode)
                    Choose payment method
                @elseif ($isRecommended)
                    Upgrade to {{ $plan->name }}
                @else
                    Upgrade
                @endif
            </a>
        @else
            <button disabled class="btn-secondary w-full opacity-50 cursor-not-allowed">Coming soon</button>
        @endif
    </div>
</div>
