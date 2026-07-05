@extends('layouts.site')

@section('title', 'My Account')

@section('content')
    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-10 sm:py-14">
        <div class="mb-10">
            <h1 class="text-3xl font-bold text-white">My account</h1>
            <p class="text-slate-400 mt-2">Welcome back, {{ $user->name }}. Manage your plan and downloads here.</p>
        </div>

        @if ($remaining === 0)
            <div class="mb-8 rounded-2xl border border-amber-500/20 bg-amber-500/10 px-5 py-4 text-sm text-amber-100">
                <p class="font-semibold text-amber-300">You have reached your monthly download limit.</p>
                <p class="mt-1">Your {{ $user->plan?->name ?? 'Free' }} plan resets on {{ $user->quota_reset_at?->format('M j, Y') ?? 'the next billing cycle' }}. <a href="{{ route('upgrade.index') }}" class="text-amber-200 underline hover:text-white">Upgrade your plan</a> for more downloads.</p>
            </div>
        @endif

        @if (session('status'))
            <div class="mb-8 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 px-5 py-4 text-sm">{{ session('status') }}</div>
        @endif

        @if ($pendingUpgradeRequests->isNotEmpty())
            <div class="mb-8 rounded-2xl border border-violet-500/20 bg-violet-500/10 px-5 py-4 text-sm text-violet-100">
                <p class="font-semibold text-violet-200">Bank transfer under review</p>
                <p class="mt-1">We are verifying your bank transfer for
                    <strong class="text-white">{{ $pendingUpgradeRequests->first()->plan?->name }}</strong>.
                    Your plan will activate after approval (usually within {{ config('billing.activation_hours', 24) }} hours on business days).
                </p>
            </div>
        @endif

        @foreach ($pendingCryptoRequests as $pendingCrypto)
            <div class="mb-8 rounded-2xl border border-slate-700/80 bg-slate-900/40 px-5 py-4 text-sm text-slate-300">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="font-semibold text-white">Open crypto checkout — {{ $pendingCrypto->plan?->name }}</p>
                        <p class="mt-1 text-slate-400">Complete payment on Plisio or cancel to try again later.</p>
                        @if ($pendingCrypto->invoice_url)
                            <a href="{{ $pendingCrypto->invoice_url }}" target="_blank" rel="noopener" class="inline-block mt-2 text-violet-400 hover:text-violet-300 underline">
                                Open checkout →
                            </a>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('account.upgrade-requests.cancel-crypto', $pendingCrypto) }}" class="shrink-0">
                        @csrf
                        <button type="submit" class="text-xs text-slate-500 hover:text-slate-300 underline">Cancel checkout</button>
                    </form>
                </div>
            </div>
        @endforeach

        @php
            $notifications = isset($upgradeNotifications)
                ? $upgradeNotifications
                : collect($recentlyApprovedUpgradeRequests ?? [])
                    ->merge($rejectedUpgradeRequests ?? [])
                    ->filter(fn ($request) => $request->user_dismissed_at === null)
                    ->sortByDesc('reviewed_at')
                    ->take(5);
        @endphp

        @foreach ($notifications as $notification)
            @include('partials.upgrade-request-notice', ['request' => $notification])
        @endforeach

        {{-- Stats --}}
        <div class="grid sm:grid-cols-3 gap-5 mb-10">
            <div class="card p-6">
                <p class="text-sm text-slate-500 font-medium">Current plan</p>
                <p class="text-2xl font-bold text-white mt-1">{{ $user->plan?->name ?? 'Free' }}</p>
                <span class="inline-block mt-3 text-xs font-medium text-violet-300 bg-violet-500/10 border border-violet-500/20 rounded-full px-3 py-1">{{ $user->subscriptionStatusLabel() }}</span>
                <p class="mt-3 text-xs text-slate-500">
                    @if ($user->isComplimentary())
                        Complimentary access — no payment required.
                    @elseif ($user->subscription_status === \App\Models\User::SUBSCRIPTION_PAST_DUE)
                        Payment overdue. Send a new transfer or contact support before access is removed.
                    @elseif ($user->subscription_renews_at)
                        Renews {{ $user->subscription_renews_at->format('M j, Y') }}.
                    @elseif ($user->subscription_ends_at)
                        Access ends {{ $user->subscription_ends_at->format('M j, Y') }}.
                    @elseif ($user->plan?->slug !== 'free')
                        Pay by bank transfer each month to keep access.
                    @else
                        Free plan — upgrade anytime.
                    @endif
                </p>
            </div>
            <div class="card p-6">
                <p class="text-sm text-slate-500 font-medium">Downloads left</p>
                <p class="text-2xl font-bold text-white mt-1">
                    @if ($remaining === null)
                        Unlimited
                    @else
                        {{ $remaining }} <span class="text-base font-normal text-slate-500">/ {{ $monthlyLimit }}</span>
                    @endif
                </p>
                <p class="text-xs text-slate-600 mt-3">Resets {{ $user->quota_reset_at?->format('M j, Y') ?? 'monthly' }}</p>
            </div>
            <div class="card p-6 flex flex-col justify-between">
                <div>
                    <p class="text-sm text-slate-500 font-medium">Upgrade</p>
                    <p class="text-lg font-semibold text-white mt-1">More downloads</p>
                    <p class="text-xs text-slate-500 mt-2">Pay monthly by bank transfer. We activate your plan after verifying payment.</p>
                </div>
                <a href="{{ route('upgrade.index') }}" class="btn-primary w-full mt-4 justify-center text-xs">Upgrade plan</a>
            </div>
        </div>

        {{-- Usage bar --}}
        @if ($remaining !== null && $monthlyLimit)
            <div class="card p-6 mb-10">
                <div class="flex justify-between text-sm mb-2">
                    <span class="text-slate-400">Monthly usage</span>
                    <span class="text-slate-300 font-medium">{{ $user->downloads_this_month }} / {{ $monthlyLimit }}</span>
                </div>
                <progress
                    value="{{ $user->downloads_this_month }}"
                    max="{{ max(1, $monthlyLimit) }}"
                    class="h-2.5 w-full overflow-hidden rounded-full [&::-webkit-progress-bar]:bg-slate-800 [&::-webkit-progress-value]:bg-gradient-to-r [&::-webkit-progress-value]:from-violet-600 [&::-webkit-progress-value]:to-fuchsia-600 [&::-moz-progress-bar]:bg-violet-600"
                ></progress>
            </div>
        @endif

        {{-- Download history --}}
        <div class="card overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-800 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-white">Your downloads</h2>
                <a href="{{ route('home') }}" class="text-sm text-violet-400 hover:text-violet-300 font-medium">+ New download</a>
            </div>

            @if ($downloads->isEmpty())
                <div class="px-6 py-16 text-center">
                    <div class="text-4xl mb-4 opacity-30">↓</div>
                    <p class="text-slate-400">No downloads yet.</p>
                    <a href="{{ route('home') }}#download" class="btn-primary mt-6 inline-flex">Paste a link</a>
                </div>
            @else
                <div class="divide-y divide-slate-800">
                    @foreach ($downloads as $download)
                        <a href="{{ route('downloads.show', $download) }}" class="flex items-center gap-4 px-6 py-4 hover:bg-slate-800/40 transition group">
                            <div class="h-10 w-10 rounded-xl bg-slate-800 flex items-center justify-center text-slate-500 group-hover:text-violet-400 transition shrink-0">
                                ↓
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-slate-200 truncate">{{ $download->media_title ?: 'Download #'.$download->id }}</p>
                                <p class="text-xs text-slate-500 truncate">{{ $download->platform?->name ?? 'Unknown' }} · {{ $download->created_at->diffForHumans() }}</p>
                                @if ($download->status === 'failed')
                                    <p class="text-xs text-red-400 mt-1 truncate">{{ $download->error_message ?: 'This download failed. Open it for retry guidance.' }}</p>
                                @elseif (in_array($download->status, ['pending', 'processing']))
                                    <p class="text-xs text-slate-500 mt-1 truncate">We are preparing your file in the background. Open it to watch progress.</p>
                                @endif
                            </div>
                            <span @class([
                                'text-xs font-semibold rounded-full px-3 py-1 shrink-0',
                                'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' => $download->status === 'completed',
                                'bg-amber-500/10 text-amber-400 border border-amber-500/20' => in_array($download->status, ['pending', 'processing']),
                                'bg-red-500/10 text-red-400 border border-red-500/20' => $download->status === 'failed',
                                'bg-slate-500/10 text-slate-400 border border-slate-500/20' => ! in_array($download->status, ['completed', 'pending', 'processing', 'failed']),
                            ])>
                                {{ ucfirst($download->status) }}
                            </span>
                        </a>
                    @endforeach
                </div>
                @if ($downloads->hasPages())
                    <div class="px-6 py-4 border-t border-slate-800">
                        {{ $downloads->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
@endsection
