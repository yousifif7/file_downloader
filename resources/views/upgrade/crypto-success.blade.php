@extends('layouts.site')

@section('title', 'Payment received — '.$plan->name)

@section('content')
    <div class="max-w-xl mx-auto px-4 sm:px-6 py-10 sm:py-14 text-center">
        @if ($isActive)
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-400 mb-6">
                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-white">{{ $plan->name }} is active</h1>
            <p class="text-slate-400 mt-3 leading-relaxed">
                Your crypto payment was confirmed and your plan is ready to use.
            </p>
        @else
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-amber-500/10 text-amber-400 mb-6">
                <svg class="h-8 w-8 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-white">Payment processing</h1>
            <p class="text-slate-400 mt-3 leading-relaxed">
                We received your payment on Plisio. Your {{ $plan->name }} plan will activate automatically once the transaction is fully confirmed on the blockchain — usually within a few minutes.
            </p>
        @endif

        <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
            <a href="{{ route('account') }}" class="btn-primary justify-center">Go to my account</a>
            <a href="{{ route('home') }}" class="btn-secondary justify-center">Start downloading</a>
        </div>

        @unless ($isActive)
            <p class="text-sm text-slate-500 mt-8">
                Still waiting after 30 minutes?
                <a href="{{ route('support.tickets.create') }}" class="text-violet-400 hover:underline">Contact support</a>
                with your payment reference.
            </p>
        @endunless
    </div>
@endsection
