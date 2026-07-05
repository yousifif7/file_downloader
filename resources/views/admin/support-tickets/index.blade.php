@extends('layouts.admin')

@section('title', 'Support')

@section('content')
    <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-white">Support inbox</h1>
            <p class="text-sm text-slate-500 mt-1">Reply to customers in-app. They see your message in their support chat and get an email notification.</p>
        </div>
        @if ($unreadCount > 0)
            <span class="inline-flex items-center rounded-full border border-amber-500/20 bg-amber-500/10 px-3 py-1 text-xs font-semibold text-amber-300">
                {{ $unreadCount }} awaiting reply
            </span>
        @endif
    </div>

    <form method="GET" class="mb-4 flex flex-col gap-2 sm:flex-row sm:flex-wrap">
        <input type="text" name="search" value="{{ $search }}" placeholder="Search subject or customer..." class="admin-input w-full sm:w-64">
        <select name="status" class="admin-input w-full sm:w-44">
            <option value="">All</option>
            <option value="unread" @selected($status === 'unread')>Awaiting reply</option>
            <option value="open" @selected($status === 'open')>Open</option>
            <option value="closed" @selected($status === 'closed')>Closed</option>
        </select>
        <button class="btn-secondary justify-center sm:justify-start">Filter</button>
    </form>

    <div class="card admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th class="col-compact">Status</th>
                    <th>Customer</th>
                    <th>Subject</th>
                    <th class="col-compact">Messages</th>
                    <th class="col-compact">Updated</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tickets as $ticket)
                    <tr @class(['bg-violet-500/5' => $ticket->hasUnreadForStaff()])>
                        <td class="col-compact">
                            @if ($ticket->hasUnreadForStaff())
                                <span class="text-xs font-semibold text-amber-300">New</span>
                            @elseif ($ticket->isClosed())
                                <span class="text-xs text-slate-500">Closed</span>
                            @else
                                <span class="text-xs text-emerald-400">Open</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.support-tickets.show', $ticket) }}" class="font-medium text-violet-400 hover:text-violet-300">{{ $ticket->user?->name ?? 'Unknown' }}</a>
                            <div class="text-xs text-slate-500 col-truncate">{{ $ticket->user?->email }}</div>
                        </td>
                        <td class="col-wrap">{{ $ticket->subject }}</td>
                        <td class="col-compact col-muted">{{ $ticket->messages_count }}</td>
                        <td class="col-compact col-muted">{{ $ticket->last_message_at?->diffForHumans() ?? $ticket->created_at->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-slate-500 py-8">No support conversations yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $tickets->links() }}</div>
@endsection
