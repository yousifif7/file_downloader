@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <h1 class="text-2xl font-bold text-white mb-6">Dashboard</h1>

    @if ($pendingUpgradeCount > 0 || $awaitingStaffTicketCount > 0)
        <div class="mb-8 space-y-4">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Needs your attention</h2>

            @if ($pendingUpgradeCount > 0)
                <div class="card p-5 border-amber-500/20 bg-amber-500/5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <p class="font-semibold text-amber-200">
                                {{ $pendingUpgradeCount }} pending bank transfer{{ $pendingUpgradeCount === 1 ? '' : 's' }}
                            </p>
                            <p class="mt-1 text-sm text-amber-100/90">Review payment receipts and activate customer plans.</p>
                        </div>
                        <a href="{{ route('admin.upgrade-requests.index', ['status' => 'pending']) }}" class="btn-primary shrink-0 !py-2 text-sm">Review upgrades</a>
                    </div>
                    @if ($recentPendingUpgrades->isNotEmpty())
                        <ul class="mt-4 space-y-2 border-t border-amber-500/10 pt-4 text-sm text-amber-100/90">
                            @foreach ($recentPendingUpgrades as $upgradeRequest)
                                <li>
                                    <strong>{{ $upgradeRequest->user?->name }}</strong>
                                    — {{ $upgradeRequest->plan?->name }}
                                    <span class="text-amber-200/70">· {{ $upgradeRequest->created_at->diffForHumans() }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif

            @if ($awaitingStaffTicketCount > 0)
                <div class="card p-5 border-violet-500/20 bg-violet-500/5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <p class="font-semibold text-violet-200">
                                {{ $awaitingStaffTicketCount }} support ticket{{ $awaitingStaffTicketCount === 1 ? '' : 's' }} awaiting reply
                            </p>
                            <p class="mt-1 text-sm text-violet-100/90">Customers are waiting for a response from your team.</p>
                        </div>
                        <a href="{{ route('admin.support-tickets.index', ['status' => 'unread']) }}" class="btn-primary shrink-0 !py-2 text-sm">Open support</a>
                    </div>
                    @if ($recentAwaitingTickets->isNotEmpty())
                        <ul class="mt-4 space-y-2 border-t border-violet-500/10 pt-4 text-sm text-violet-100/90">
                            @foreach ($recentAwaitingTickets as $ticket)
                                <li>
                                    <a href="{{ route('admin.support-tickets.show', $ticket) }}" class="hover:text-white transition">
                                        <strong>{{ $ticket->subject }}</strong>
                                        — {{ $ticket->user?->name }}
                                        <span class="text-violet-200/70">· {{ $ticket->last_message_at?->diffForHumans() }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4 mb-8">
        @foreach ([
            ['Today', $downloadsToday, 'text-white'],
            ['Last 7 days', $downloadsWeek, 'text-white'],
            ['Total downloads', $downloadsTotal, 'text-white'],
            ['Failed', $failedDownloads, 'text-red-400'],
            ['Users', $usersCount, 'text-white'],
        ] as [$label, $value, $color])
            <div class="card p-4">
                <div class="text-sm text-slate-500">{{ $label }}</div>
                <div class="text-2xl font-bold {{ $color }}">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="card overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-800 font-medium text-white">Recent downloads</div>
            <div class="divide-y divide-slate-800">
                @forelse ($recentDownloads as $download)
                    <a href="{{ route('admin.downloads.show', $download) }}" class="block px-4 py-3 hover:bg-slate-800/50 transition">
                        <div class="font-medium text-slate-200 truncate">{{ $download->media_title ?: $download->source_url }}</div>
                        <div class="text-sm text-slate-500">{{ $download->status }} · {{ $download->created_at->diffForHumans() }}</div>
                    </a>
                @empty
                    <div class="px-4 py-6 text-slate-500 text-sm">No downloads yet.</div>
                @endforelse
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-800 font-medium text-white">Top platforms</div>
            <div class="divide-y divide-slate-800">
                @forelse ($topPlatforms as $row)
                    <div class="px-4 py-3 flex justify-between text-slate-300">
                        <span>{{ $row->platform?->name ?? 'Unknown' }}</span>
                        <span class="text-slate-500">{{ $row->total }}</span>
                    </div>
                @empty
                    <div class="px-4 py-6 text-slate-500 text-sm">No platform data yet.</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
