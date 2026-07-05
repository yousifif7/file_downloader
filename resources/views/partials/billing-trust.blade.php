@props(['compact' => false])

@php
    $activationHours = config('billing.activation_hours', 24);
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
        ])>Why we use bank transfer</h3>
        @unless ($compact)
            <p class="mt-2 text-sm text-slate-400 max-w-2xl mx-auto leading-relaxed">
                A simple, secure way to pay that keeps your card details off our site and helps us offer lower monthly prices.
            </p>
        @endunless
    </div>

    <div @class([
        'grid gap-4',
        'sm:grid-cols-3' => ! $compact,
        'gap-3' => $compact,
    ])>
        <div class="rounded-xl border border-slate-800/80 bg-slate-950/50 p-4">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-violet-500/10 text-violet-400 text-sm font-bold mb-3" aria-hidden="true">1</div>
            <p class="text-sm font-medium text-white">Fast activation</p>
            <p class="mt-1.5 text-xs text-slate-400 leading-relaxed">
                Submit your transfer receipt after payment. We usually verify and activate your plan within {{ $activationHours }} hours on business days.
            </p>
        </div>

        <div class="rounded-xl border border-slate-800/80 bg-slate-950/50 p-4">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-400 text-sm font-bold mb-3" aria-hidden="true">2</div>
            <p class="text-sm font-medium text-white">Your money is safe</p>
            <p class="mt-1.5 text-xs text-slate-400 leading-relaxed">
                You pay directly through your own bank — we never touch your card. Every payment is reviewed against your receipt before your plan goes live.
            </p>
        </div>

        <div class="rounded-xl border border-slate-800/80 bg-slate-950/50 p-4">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-fuchsia-500/10 text-fuchsia-400 text-sm font-bold mb-3" aria-hidden="true">3</div>
            <p class="text-sm font-medium text-white">Your info stays private</p>
            <p class="mt-1.5 text-xs text-slate-400 leading-relaxed">
                We do not store credit card numbers or bank login details — only your transfer reference and receipt screenshot for verification.
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
