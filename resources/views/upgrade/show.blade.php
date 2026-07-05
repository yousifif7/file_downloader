@extends('layouts.site')

@section('title', 'Pay for '.$plan->name)

@section('content')
    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-10 sm:py-14">
        <a href="{{ route('upgrade.index') }}" class="text-sm text-violet-400 hover:text-violet-300">← All plans</a>

        <div class="mt-4">
            <h1 class="text-3xl font-bold text-white">{{ $plan->name }}</h1>
            <p class="text-slate-400 mt-2">
                {{ $catalog->priceLabel($plan) }} {{ config('billing.currency') }} per month · {{ $catalog->monthlyLimitLabel($plan) }}
            </p>
        </div>

        @if ($errors->any())
            <div class="mt-6 rounded-xl border border-red-500/20 bg-red-500/10 px-5 py-4 text-sm text-red-300">
                {{ $errors->first() }}
            </div>
        @endif

        @if ($pendingCrypto?->invoice_url)
            <div class="mt-8 rounded-xl border border-slate-700/80 bg-slate-900/40 px-5 py-4 text-sm text-slate-300">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="font-semibold text-white">You have an open crypto checkout</p>
                        <p class="mt-1 text-slate-400">Complete payment on Plisio or cancel below to start again.</p>
                        <a href="{{ $pendingCrypto->invoice_url }}" target="_blank" rel="noopener" class="inline-flex mt-3 text-sm font-medium text-violet-400 underline hover:text-violet-300">
                            Open Plisio checkout →
                        </a>
                    </div>
                    <form method="POST" action="{{ route('account.upgrade-requests.cancel-crypto', $pendingCrypto) }}">
                        @csrf
                        <button type="submit" class="text-xs text-slate-500 hover:text-slate-300 underline">Cancel checkout</button>
                    </form>
                </div>
            </div>
        @endif

        <div class="mt-8">
            <h2 class="text-lg font-semibold text-white">Choose how to pay</h2>
            <p class="text-sm text-slate-400 mt-1">Pick the option that works best for you. Both are billed monthly.</p>
        </div>

        <div @class([
            'mt-6 grid gap-4',
            'sm:grid-cols-2' => $cryptoAvailable && $bankConfigured,
        ])>
            @if ($bankConfigured)
                <a href="{{ route('upgrade.bank', $plan) }}" class="group relative flex flex-col rounded-2xl border border-slate-800/80 bg-slate-900/60 p-6 transition hover:border-violet-500/40 hover:bg-slate-900/80 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500/50">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-500/10 text-violet-400">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25m2.25 0v.75a.75.75 0 0 1-.75.75h-.75m0 0H3m18 0v.375c0 .621-.504 1.125-1.125 1.125H3.75m0 0v3.375c0 .621.504 1.125 1.125 1.125h16.5a1.125 1.125 0 0 0 1.125-1.125V7.5" />
                            </svg>
                        </div>
                        <span class="rounded-full border border-slate-700 bg-slate-950/80 px-2.5 py-0.5 text-[11px] font-medium uppercase tracking-wide text-slate-400">
                            Manual review
                        </span>
                    </div>

                    <h3 class="mt-5 text-lg font-semibold text-white group-hover:text-violet-100">Bank transfer</h3>
                    <p class="mt-2 text-sm text-slate-400 leading-relaxed flex-1">
                        Pay from your local bank (e.g. Bank of Palestine). Upload your receipt and we activate your plan after verification.
                    </p>

                    <ul class="mt-4 space-y-2 text-xs text-slate-500">
                        <li class="flex items-center gap-2">
                            <span class="h-1 w-1 rounded-full bg-slate-600" aria-hidden="true"></span>
                            Usually within {{ config('legal.support_response_hours') }} hours
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="h-1 w-1 rounded-full bg-slate-600" aria-hidden="true"></span>
                            Best if you prefer local banking
                        </li>
                    </ul>

                    <span class="mt-6 inline-flex items-center text-sm font-medium text-violet-400 group-hover:text-violet-300">
                        Continue with bank transfer
                        <svg class="ml-1.5 h-4 w-4 transition group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </span>
                </a>
            @endif

            @if ($cryptoAvailable)
                <form method="POST" action="{{ route('upgrade.crypto.store', $plan) }}" class="group relative flex flex-col rounded-2xl border border-emerald-500/30 bg-gradient-to-b from-emerald-500/5 to-slate-900/60 p-6 transition hover:border-emerald-500/50 hover:from-emerald-500/10 focus-within:ring-2 focus-within:ring-emerald-500/40">
                    @csrf

                    <div class="flex items-start justify-between gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-400">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </div>
                        <span class="rounded-full border border-emerald-500/30 bg-emerald-500/10 px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-emerald-300">
                            Instant
                        </span>
                    </div>

                    <h3 class="mt-5 text-lg font-semibold text-white">Crypto (USDT)</h3>
                    <p class="mt-2 text-sm text-slate-400 leading-relaxed flex-1">
                        Pay with USDT or other supported coins via Plisio. Your plan activates automatically once payment is confirmed on-chain.
                    </p>

                    <ul class="mt-4 space-y-2 text-xs text-slate-500">
                        <li class="flex items-center gap-2">
                            <span class="h-1 w-1 rounded-full bg-emerald-600/80" aria-hidden="true"></span>
                            Auto-activated — no waiting for review
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="h-1 w-1 rounded-full bg-emerald-600/80" aria-hidden="true"></span>
                            USDT, BTC, LTC supported
                        </li>
                    </ul>

                    <button type="submit" class="mt-6 inline-flex items-center text-sm font-medium text-emerald-400 hover:text-emerald-300 text-left">
                        Pay with crypto
                        <svg class="ml-1.5 h-4 w-4 transition group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </button>
                </form>
            @elseif (! $bankConfigured)
                <div class="rounded-2xl border border-amber-500/20 bg-amber-500/10 px-5 py-4 text-sm text-amber-100 sm:col-span-2">
                    Payment options are not configured yet. Please contact
                    <a href="{{ route('support.tickets.create') }}" class="text-amber-200 underline">support</a> to complete your upgrade.
                </div>
            @else
                <div class="rounded-2xl border border-slate-800/80 bg-slate-900/40 px-5 py-4 text-sm text-slate-400 sm:col-span-2">
                    Crypto payments will appear here once API keys are configured on the server.
                </div>
            @endif
        </div>

        @unless ($bankConfigured)
            @if ($cryptoAvailable)
                {{-- Crypto-only mode when bank is not configured --}}
            @else
                <div class="mt-6 rounded-xl border border-amber-500/20 bg-amber-500/10 px-5 py-4 text-sm text-amber-100">
                    Bank details are not configured on the server yet. Please contact
                    <a href="{{ route('support.tickets.create') }}" class="text-amber-200 underline">support</a> to complete your upgrade.
                </div>
            @endif
        @endunless

        <div class="mt-10">
            @include('partials.billing-trust', ['compact' => true])
        </div>

        <p class="text-center text-sm text-slate-500 mt-8">
            Questions? <a href="{{ route('support.tickets.create') }}" class="text-violet-400 hover:underline">Contact support</a>.
        </p>
    </div>
@endsection
