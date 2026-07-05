@props(['compact' => false])

@php
    $activationHours = config('billing.activation_hours', 24);
    $cryptoAvailable = config('billing.crypto.enabled') && filled(config('billing.crypto.api_key'));
@endphp

<div @class([
    'rounded-2xl border border-slate-800/80 bg-slate-900/40',
    'p-6 sm:p-8' => ! $compact,
    'p-5' => $compact,
])>
    <div @class(['text-center' => ! $compact, 'mb-6' => ! $compact, 'mb-4' => $compact])>
        <h3 @class([
            'font-semibold text-white',
            'text-lg' => ! $compact,
            'text-base' => $compact,
        ])>Simple, secure billing</h3>
        @unless ($compact)
            <p class="mt-2 text-sm text-slate-400 max-w-2xl mx-auto leading-relaxed">
                Pay the way that suits you — local bank transfer or crypto with instant activation. We never store card numbers on our servers.
            </p>
        @endunless
    </div>

    <div @class([
        'grid gap-4',
        'sm:grid-cols-3' => ! $compact && $cryptoAvailable,
        'sm:grid-cols-2' => ! $compact && ! $cryptoAvailable,
        'gap-3' => $compact,
    ])>
        @if ($cryptoAvailable)
            <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/5 p-4">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-400 text-sm font-bold mb-3" aria-hidden="true">⚡</div>
                <p class="text-sm font-medium text-white">Instant with crypto</p>
                <p class="mt-1.5 text-xs text-slate-400 leading-relaxed">
                    Pay with USDT or other coins. Your plan activates automatically once payment is confirmed — no manual review.
                </p>
            </div>
        @endif

        <div class="rounded-xl border border-slate-800/80 bg-slate-950/50 p-4">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-violet-500/10 text-violet-400 text-sm font-bold mb-3" aria-hidden="true">1</div>
            <p class="text-sm font-medium text-white">Bank transfer option</p>
            <p class="mt-1.5 text-xs text-slate-400 leading-relaxed">
                Submit your transfer receipt after payment. We usually verify and activate your plan within {{ $activationHours }} hours on business days.
            </p>
        </div>

        <div class="rounded-xl border border-slate-800/80 bg-slate-950/50 p-4">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-fuchsia-500/10 text-fuchsia-400 text-sm font-bold mb-3" aria-hidden="true">2</div>
            <p class="text-sm font-medium text-white">Your info stays private</p>
            <p class="mt-1.5 text-xs text-slate-400 leading-relaxed">
                We do not store credit card numbers or bank login details — only what is needed to verify your payment.
            </p>
        </div>
    </div>

    <p @class([
        'text-slate-500 leading-relaxed',
        'text-center text-xs mt-6' => ! $compact,
        'text-xs mt-4' => $compact,
    ])>
        Questions about billing? See our
        <a href="{{ route('refund') }}" class="text-violet-400 hover:text-violet-300 transition">Refund Policy</a>
        or <a href="{{ route('support') }}" class="text-violet-400 hover:text-violet-300 transition">contact support</a>.
    </p>
</div>
