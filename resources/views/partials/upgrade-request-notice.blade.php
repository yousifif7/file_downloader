@props(['request'])

@php
    $isApproved = $request->status === \App\Models\PlanUpgradeRequest::STATUS_APPROVED;
@endphp

<div @class([
    'mb-8 rounded-2xl border p-5 text-sm',
    'border-emerald-500/20 bg-emerald-500/10 text-emerald-100' => $isApproved,
    'border-red-500/20 bg-red-500/10 text-red-100' => ! $isApproved,
])>
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0 flex-1">
            @if ($isApproved)
                <p class="font-semibold text-emerald-200">Upgrade approved</p>
                <p class="mt-1">
                    Your payment was verified and <strong class="text-white">{{ $request->plan?->name }}</strong> is now active.
                    @if ($request->reviewed_at)
                        Approved {{ $request->reviewed_at->diffForHumans() }}.
                    @endif
                </p>
            @else
                <p class="font-semibold text-red-200">Upgrade not approved</p>
                <p class="mt-1">
                    We could not verify your payment for <strong class="text-white">{{ $request->plan?->name }}</strong>.
                    @if ($request->reviewed_at)
                        Reviewed {{ $request->reviewed_at->diffForHumans() }}.
                    @endif
                </p>
                @if ($request->admin_note)
                    <p class="mt-3 rounded-xl border border-red-500/20 bg-red-500/5 px-4 py-3 text-red-50">
                        <span class="font-medium text-red-200">Reason:</span> {{ $request->admin_note }}
                    </p>
                @else
                    <p class="mt-2 text-red-200/90">Please double-check your transfer receipt and payment reference, then try again.</p>
                @endif
                <p class="mt-3">
                    <a href="{{ route('upgrade.index') }}" class="text-red-200 underline hover:text-white">Submit a new payment</a>
                    or <a href="{{ route('support.tickets.create') }}" class="text-red-200 underline hover:text-white">contact support</a> if you need help.
                </p>
            @endif
        </div>

        <form method="POST" action="{{ route('account.upgrade-requests.dismiss', $request) }}" class="shrink-0">
            @csrf
            <button
                type="submit"
                class="inline-flex items-center gap-1.5 rounded-lg border border-white/10 px-2.5 py-1.5 text-xs font-medium text-slate-300 transition hover:border-white/20 hover:bg-white/5 hover:text-white"
                aria-label="Dismiss notification"
                title="Dismiss"
            >
                <span aria-hidden="true">&times;</span>
                <span>Dismiss</span>
            </button>
        </form>
    </div>
</div>
