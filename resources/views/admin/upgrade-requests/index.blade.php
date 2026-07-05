@extends('layouts.admin')

@section('title', 'Upgrade requests')

@section('content')
    <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-white">Upgrade requests</h1>
            <p class="text-sm text-slate-500 mt-1">Review bank transfer payments and monitor crypto checkouts.</p>
        </div>
        @if ($pendingCount > 0)
            <span class="inline-flex items-center rounded-full border border-amber-500/20 bg-amber-500/10 px-3 py-1 text-xs font-semibold text-amber-300">
                {{ $pendingCount }} pending
            </span>
        @endif
    </div>

    <form method="GET" class="mb-4 flex flex-col gap-2 sm:flex-row sm:flex-wrap">
        <input type="text" name="search" value="{{ $search }}" placeholder="Search email or reference..." class="admin-input w-full sm:w-64">
        <select name="status" class="admin-input w-full sm:w-44">
            <option value="">All statuses</option>
            <option value="pending" @selected($status === 'pending')>Pending</option>
            <option value="approved" @selected($status === 'approved')>Approved</option>
            <option value="rejected" @selected($status === 'rejected')>Rejected</option>
            <option value="cancelled" @selected($status === 'cancelled')>Cancelled</option>
        </select>
        <button class="btn-secondary justify-center sm:justify-start">Filter</button>
    </form>

    <div class="space-y-4">
        @forelse ($requests as $upgradeRequest)
            <div @class(['card p-6', 'border-amber-500/30 bg-amber-500/5' => $upgradeRequest->isPending()])>
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0 flex-1 space-y-2 text-sm">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-semibold text-white">{{ $upgradeRequest->user?->name }}</span>
                            <span class="text-slate-500">{{ $upgradeRequest->user?->email }}</span>
                            <span @class([
                                'text-xs font-semibold rounded-full px-2.5 py-0.5',
                                'bg-amber-500/10 text-amber-300 border border-amber-500/20' => $upgradeRequest->isPending(),
                                'bg-emerald-500/10 text-emerald-300 border border-emerald-500/20' => $upgradeRequest->status === 'approved',
                                'bg-red-500/10 text-red-300 border border-red-500/20' => $upgradeRequest->status === 'rejected',
                                'bg-slate-500/10 text-slate-400 border border-slate-600/40' => $upgradeRequest->status === 'cancelled',
                            ])>{{ $upgradeRequest->statusLabel() }}</span>
                            <span class="text-xs rounded-full px-2.5 py-0.5 border border-slate-700 text-slate-400">{{ $upgradeRequest->paymentMethodLabel() }}</span>
                        </div>
                        <p class="text-slate-300">
                            Wants <strong class="text-white">{{ $upgradeRequest->plan?->name }}</strong>
                            @if ($upgradeRequest->plan?->price_cents)
                                — ${{ number_format($upgradeRequest->plan->price_cents / 100, 2) }}/mo
                            @endif
                        </p>
                        <p class="text-slate-400">
                            Reference: <span class="font-mono text-slate-200">{{ $upgradeRequest->payment_reference }}</span>
                            · Submitted {{ $upgradeRequest->created_at->format('M j, Y g:i A') }}
                        </p>
                        @if ($upgradeRequest->payer_note)
                            <p class="text-slate-400">Customer note: {{ $upgradeRequest->payer_note }}</p>
                        @endif
                        @if ($upgradeRequest->isCrypto() && $upgradeRequest->invoice_url)
                            <p class="text-slate-400">
                                <a href="{{ $upgradeRequest->invoice_url }}" target="_blank" rel="noopener" class="text-violet-400 hover:text-violet-300">View Plisio invoice →</a>
                                @if ($upgradeRequest->provider_payment_id)
                                    · Txn {{ $upgradeRequest->provider_payment_id }}
                                @endif
                            </p>
                        @endif
                        @if ($upgradeRequest->receiptUrl())
                            <div class="pt-2">
                                <p class="text-slate-500 text-xs uppercase tracking-wide mb-2">Transfer receipt</p>
                                <a href="{{ $upgradeRequest->receiptUrl() }}" target="_blank" rel="noopener" class="inline-block">
                                    @if (str_ends_with(strtolower($upgradeRequest->receipt_path ?? ''), '.pdf'))
                                        <span class="text-violet-400 hover:text-violet-300 text-sm font-medium">View PDF receipt →</span>
                                    @else
                                        <img src="{{ $upgradeRequest->receiptUrl() }}" alt="Payment receipt" class="max-h-48 rounded-lg border border-slate-700">
                                    @endif
                                </a>
                            </div>
                        @endif
                        @if ($upgradeRequest->admin_note)
                            <p class="text-slate-500">Admin note: {{ $upgradeRequest->admin_note }}</p>
                        @endif
                        @if ($upgradeRequest->reviewed_at)
                            <p class="text-xs text-slate-600">Reviewed {{ $upgradeRequest->reviewed_at->diffForHumans() }} by {{ $upgradeRequest->reviewer?->name ?? 'admin' }}</p>
                        @endif
                    </div>

                    @if ($upgradeRequest->isPending() && $upgradeRequest->isBankTransfer())
                        <div class="flex flex-col gap-2 w-full lg:w-72 shrink-0">
                            <form method="POST" action="{{ route('admin.upgrade-requests.approve', $upgradeRequest) }}" class="space-y-2">
                                @csrf
                                <input type="text" name="admin_note" class="admin-input text-xs" placeholder="Optional note">
                                <button type="submit" class="btn-primary w-full justify-center !py-2 text-sm">Approve &amp; activate plan</button>
                            </form>
                            <form method="POST" action="{{ route('admin.upgrade-requests.reject', $upgradeRequest) }}" class="space-y-2">
                                @csrf
                                <input type="text" name="admin_note" class="admin-input text-xs" placeholder="Reason (optional)">
                                <button type="submit" class="btn-secondary w-full justify-center !py-2 text-sm">Reject</button>
                            </form>
                            <a href="{{ route('admin.users.show', $upgradeRequest->user) }}" class="text-center text-xs text-violet-400 hover:text-violet-300">View user account</a>
                        </div>
                    @elseif ($upgradeRequest->isPending() && $upgradeRequest->isCrypto())
                        <p class="text-sm text-slate-400 shrink-0 max-w-xs">Waiting for on-chain confirmation — activates automatically.</p>
                    @else
                        <a href="{{ route('admin.users.show', $upgradeRequest->user) }}" class="text-sm text-violet-400 hover:text-violet-300 shrink-0">View user</a>
                    @endif
                </div>
            </div>
        @empty
            <div class="card p-10 text-center text-slate-500">No upgrade requests yet.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $requests->links() }}</div>
@endsection
