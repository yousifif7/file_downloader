@extends('layouts.admin')

@section('title', 'Downloads')

@section('content')
    <div class="mb-6 flex items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold text-white">Downloads</h1>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6 mb-6">
        @foreach ([
            ['Matching', $summary['matching'], 'text-white'],
            ['Today', $summary['today'], 'text-white'],
            ['Pending', $summary['pending'], 'text-amber-400'],
            ['Processing', $summary['processing'], 'text-amber-400'],
            ['Completed', $summary['completed'], 'text-emerald-400'],
            ['Failure rate', $summary['failureRate'].'%', 'text-red-400'],
        ] as [$label, $value, $color])
            <div class="card p-4">
                <div class="text-sm text-slate-500">{{ $label }}</div>
                <div class="text-2xl font-bold {{ $color }}">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    <form method="GET" class="mb-6 card p-4">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-6">
            <input type="text" name="search" value="{{ $search }}" placeholder="Search URL or title" class="admin-input">
            <select name="status" class="admin-input">
                <option value="">All statuses</option>
                @foreach (['pending', 'processing', 'completed', 'failed', 'expired'] as $option)
                    <option value="{{ $option }}" @selected($status === $option)>{{ ucfirst($option) }}</option>
                @endforeach
            </select>
            <select name="platform_id" class="admin-input">
                <option value="">All platforms</option>
                @foreach ($platforms as $platform)
                    <option value="{{ $platform->id }}" @selected((string) $platformId === (string) $platform->id)>{{ $platform->name }}</option>
                @endforeach
            </select>
            <select name="failure_type" class="admin-input">
                <option value="">All failure types</option>
                @foreach ($failureTypes as $type => $label)
                    <option value="{{ $type }}" @selected($failureType === $type)>{{ $label }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" value="{{ $dateFrom }}" class="admin-input">
            <input type="date" name="date_to" value="{{ $dateTo }}" class="admin-input">
        </div>
        <div class="mt-3 flex flex-col gap-2 sm:flex-row">
            <button class="btn-secondary justify-center sm:justify-start">Apply filters</button>
            <a href="{{ route('admin.downloads.index') }}" class="btn-secondary justify-center sm:justify-start">Reset</a>
        </div>
    </form>

    <div class="grid gap-6 xl:grid-cols-2 mb-6">
        <div class="card p-4">
            <h2 class="text-sm font-semibold text-white mb-3">Failure causes</h2>
            <div class="space-y-3">
                @forelse ($failureBreakdown as $row)
                    <div class="flex items-center justify-between gap-3 text-sm">
                        <span class="text-slate-300">{{ $row['label'] }}</span>
                        <span class="rounded-full border border-slate-700 px-3 py-1 text-slate-400">{{ $row['count'] }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No failed downloads in this view.</p>
                @endforelse
            </div>
        </div>

        <div class="card p-4">
            <h2 class="text-sm font-semibold text-white mb-3">Platforms under pressure</h2>
            <div class="space-y-3">
                @forelse ($platformBreakdown as $row)
                    <div class="flex items-center justify-between gap-3 text-sm">
                        <div>
                            <div class="text-slate-300">{{ $row->platform_name }}</div>
                            <div class="text-xs text-slate-500">{{ $row->failed }} failed / {{ $row->total }} total</div>
                        </div>
                        <span class="rounded-full border border-slate-700 px-3 py-1 text-slate-400">
                            {{ $row->total > 0 ? round(($row->failed / $row->total) * 100) : 0 }}%
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No platform activity in this view.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="card admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th class="col-compact">ID</th>
                    <th>User</th>
                    <th>Title / URL</th>
                    <th class="col-compact">Platform</th>
                    <th class="col-compact">Status</th>
                    <th class="col-compact">Failure</th>
                    <th class="col-compact">Created</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($downloads as $download)
                    <tr>
                        <td class="col-compact">
                            <a href="{{ route('admin.downloads.show', $download) }}" class="text-violet-400 hover:text-violet-300">#{{ $download->id }}</a>
                        </td>
                        <td class="col-truncate">{{ $download->user?->email ?? '—' }}</td>
                        <td class="col-truncate text-slate-300" title="{{ $download->media_title ?: $download->source_url }}">{{ $download->media_title ?: $download->source_url }}</td>
                        <td class="col-muted col-compact">{{ $download->platform?->name ?? '—' }}</td>
                        <td class="col-compact">
                            <span @class([
                                'inline-flex rounded-full px-3 py-1 text-xs font-medium border',
                                'border-emerald-500/20 bg-emerald-500/10 text-emerald-400' => $download->status === 'completed',
                                'border-amber-500/20 bg-amber-500/10 text-amber-400' => in_array($download->status, ['pending', 'processing']),
                                'border-red-500/20 bg-red-500/10 text-red-400' => $download->status === 'failed',
                                'border-slate-700 bg-slate-800 text-slate-400' => $download->status === 'expired',
                            ])>
                                {{ ucfirst($download->status) }}
                            </span>
                        </td>
                        <td class="col-muted col-compact">
                            {{ $downloadFailureLabels[$download->id] ?? '—' }}
                        </td>
                        <td class="col-muted col-compact whitespace-nowrap">{{ $download->created_at->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-slate-500 py-8">No downloads match the current filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $downloads->links() }}</div>
@endsection
