@extends('layouts.site')

@section('title', 'Pay for '.$plan->name)

@section('content')
    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-10 sm:py-14">
        <a href="{{ route('upgrade.index') }}" class="text-sm text-violet-400 hover:text-violet-300">← All plans</a>
        <h1 class="text-3xl font-bold text-white mt-4">{{ $plan->name }} — bank transfer</h1>
        <p class="text-slate-400 mt-2">{{ $catalog->priceLabel($plan) }} per month · {{ $catalog->monthlyLimitLabel($plan) }}</p>

        <div class="mt-6">
            @include('partials.billing-trust', ['compact' => true])
        </div>

        @unless ($bankConfigured)
            <div class="mt-8 rounded-xl border border-amber-500/20 bg-amber-500/10 px-5 py-4 text-sm text-amber-100">
                Bank details are not configured on the server yet. Please contact
                <a href="{{ route('support.tickets.create') }}" class="text-amber-200 underline">support</a> to complete your upgrade.
            </div>
        @else
            <div class="mt-8 card p-6 space-y-4 text-sm">
                <h2 class="text-lg font-semibold text-white">1. Send payment</h2>
                <p class="text-slate-400">Transfer the monthly amount to the account below. Use the payment reference exactly as shown.</p>

                <dl class="grid gap-3 sm:grid-cols-2 text-slate-300">
                    <div>
                        <dt class="text-slate-500 text-xs uppercase tracking-wide">Amount</dt>
                        <dd class="font-semibold text-white mt-1">{{ $catalog->priceLabel($plan) }} {{ config('billing.currency') }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 text-xs uppercase tracking-wide">Payment reference</dt>
                        <dd class="font-mono text-violet-300 mt-1 break-all">{{ $paymentReference }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 text-xs uppercase tracking-wide">Bank</dt>
                        <dd class="mt-1">{{ config('billing.bank_name') }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 text-xs uppercase tracking-wide">Account holder</dt>
                        <dd class="mt-1">{{ config('billing.account_holder') }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-slate-500 text-xs uppercase tracking-wide">IBAN</dt>
                        <dd class="font-mono mt-1 break-all">{{ config('billing.iban') }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500 text-xs uppercase tracking-wide">SWIFT / BIC</dt>
                        <dd class="font-mono mt-1">{{ config('billing.swift') }}</dd>
                    </div>
                </dl>

                @if (config('billing.extra_instructions'))
                    <p class="text-slate-400 pt-2 border-t border-slate-800">{{ config('billing.extra_instructions') }}</p>
                @endif
            </div>

            <div class="mt-8 card p-6">
                <h2 class="text-lg font-semibold text-white mb-4">2. Confirm your payment</h2>
                <p class="text-sm text-slate-400 mb-6">After you transfer, upload a screenshot of your bank receipt and submit the reference so we can verify and activate your plan.</p>

                <form method="POST" action="{{ route('upgrade.store', $plan) }}" enctype="multipart/form-data" class="space-y-5">
                    @csrf

                    <div>
                        <label for="payment_reference" class="label-dark">Bank transfer reference</label>
                        <input id="payment_reference" type="text" name="payment_reference" value="{{ old('payment_reference', $paymentReference) }}" required class="input-dark mt-1.5" placeholder="Reference from your bank receipt">
                        @error('payment_reference')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="receipt" class="label-dark">Transfer receipt screenshot</label>
                        <input id="receipt" type="file" name="receipt" accept="image/jpeg,image/png,image/webp,application/pdf" required class="input-dark mt-1.5 file:mr-4 file:rounded-lg file:border-0 file:bg-violet-600 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-violet-500">
                        <p class="mt-1.5 text-xs text-slate-500">JPG, PNG, WEBP, or PDF — max 5 MB.</p>
                        @error('receipt')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="payer_note" class="label-dark">Note <span class="text-slate-500">(optional)</span></label>
                        <textarea id="payer_note" name="payer_note" rows="3" class="input-dark mt-1.5" placeholder="Transfer date, sender name, or anything that helps us verify">{{ old('payer_note') }}</textarea>
                        @error('payer_note')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <label class="flex items-start gap-3 text-sm text-slate-400">
                        <input type="checkbox" name="confirmed" value="1" required @checked(old('confirmed')) class="mt-1 rounded border-slate-600 bg-slate-900 text-violet-500 focus:ring-violet-500">
                        <span>I have sent {{ $catalog->priceLabel($plan) }} {{ config('billing.currency') }} by bank transfer and the details above are correct.</span>
                    </label>
                    @error('confirmed')<p class="text-sm text-red-400">{{ $message }}</p>@enderror

                    <button type="submit" class="btn-primary">Submit for review</button>
                </form>
            </div>
        @endunless

        <p class="text-center text-sm text-slate-500 mt-8">
            We usually activate plans within {{ config('legal.support_response_hours') }} hours on business days.
            Questions? <a href="{{ route('support.tickets.create') }}" class="text-violet-400 hover:underline">Contact support</a>.
        </p>
    </div>
@endsection
