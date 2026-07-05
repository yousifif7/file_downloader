@extends('layouts.site')

@section('title', 'Upgrade plan')

@section('content')
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-10 sm:py-14">
        <div class="mb-8">
            <a href="{{ route('account') }}" class="text-sm text-violet-400 hover:text-violet-300">← Back to account</a>
            <h1 class="text-3xl font-bold text-white mt-4">Upgrade your plan</h1>
            <p class="text-slate-400 mt-2">Choose a plan, then pay by bank transfer or crypto — whichever is easier for you.</p>
        </div>

        @if (session('status'))
            <div class="mb-8 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 px-5 py-4 text-sm">{{ session('status') }}</div>
        @endif

        @if ($pendingRequests->isNotEmpty())
            <div class="mb-8 card p-5 border-amber-500/20 bg-amber-500/5">
                <p class="text-sm font-semibold text-amber-200">Bank transfer under review</p>
                <ul class="mt-3 space-y-2 text-sm text-amber-100/90">
                    @foreach ($pendingRequests as $pending)
                        <li>
                            <strong>{{ $pending->plan?->name }}</strong>
                            — submitted {{ $pending->created_at->diffForHumans() }}
                            (ref: {{ $pending->payment_reference }})
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @foreach ($pendingCryptoRequests as $pendingCrypto)
            <div class="mb-8 card p-5 border-slate-700/80 bg-slate-900/40">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="text-sm text-slate-300">
                        <p class="font-semibold text-white">Open crypto checkout — {{ $pendingCrypto->plan?->name }}</p>
                        <p class="mt-1 text-slate-400">Finish payment on Plisio or cancel to start fresh.</p>
                        @if ($pendingCrypto->invoice_url)
                            <a href="{{ $pendingCrypto->invoice_url }}" target="_blank" rel="noopener" class="inline-block mt-2 text-violet-400 hover:text-violet-300 underline">
                                Open checkout →
                            </a>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('account.upgrade-requests.cancel-crypto', $pendingCrypto) }}">
                        @csrf
                        <button type="submit" class="text-xs text-slate-500 hover:text-slate-300 underline">Cancel</button>
                    </form>
                </div>
            </div>
        @endforeach

        @foreach ($rejectedRequests as $rejected)
            @include('partials.upgrade-request-notice', ['request' => $rejected])
        @endforeach

        @if ($paidPlans->isEmpty())
            <div class="card p-10 text-center text-slate-400">
                Paid plans are not configured yet. Check back soon or <a href="{{ route('support.tickets.create') }}" class="text-violet-400 hover:underline">contact support</a>.
            </div>
        @else
            @php
                $currentPlan = app(\App\Services\SubscriptionAccessService::class)->effectivePlanFor($user);
            @endphp
            <div @class([
                'grid gap-6 lg:gap-8 items-stretch mb-16 lg:mb-20',
                'lg:grid-cols-3' => $paidPlans->count() >= 3,
                'sm:grid-cols-2' => $paidPlans->count() === 2,
            ])>
                @foreach ($paidPlans as $plan)
                    @include('partials.plan-pricing-card', [
                        'plan' => $plan,
                        'catalog' => $catalog,
                        'plans' => $allPlans,
                        'currentPlan' => $currentPlan,
                        'upgradeMode' => true,
                    ])
                @endforeach
            </div>

            @include('partials.billing-trust')
        @endif
    </div>
@endsection
