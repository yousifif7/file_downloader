@extends('layouts.admin')

@section('title', 'Support #'.$ticket->id)

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.support-tickets.index') }}" class="text-sm text-violet-400 hover:text-violet-300">← Back to support inbox</a>
        <div class="mt-3 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-white">{{ $ticket->subject }}</h1>
                <p class="text-sm text-slate-500 mt-1">Conversation #{{ $ticket->id }} · started {{ $ticket->created_at->format('M j, Y g:i A') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($ticket->isOpen())
                    <form method="POST" action="{{ route('admin.support-tickets.close', $ticket) }}">
                        @csrf
                        <button type="submit" class="btn-secondary !py-2 text-xs">Close conversation</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.support-tickets.reopen', $ticket) }}">
                        @csrf
                        <button type="submit" class="btn-secondary !py-2 text-xs">Reopen</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6">
            <div class="card p-6 min-h-[360px] max-h-[65vh] overflow-y-auto">
                @include('partials.support-chat', ['messages' => $ticket->messages])
            </div>

            @if ($ticket->isOpen())
                <div class="card p-6">
                    <h2 class="text-sm font-semibold text-white mb-4">Reply to customer</h2>
                    <form method="POST" action="{{ route('admin.support-tickets.messages.store', $ticket) }}" class="space-y-4">
                        @csrf
                        <textarea name="message" rows="5" required class="admin-input min-h-[120px]" placeholder="Write your reply...">{{ old('message') }}</textarea>
                        @error('message')<p class="text-sm text-red-400">{{ $message }}</p>@enderror
                        <button type="submit" class="btn-primary">Send reply</button>
                    </form>
                    <p class="text-xs text-slate-500 mt-3">The customer will see this in their support chat and receive an email with a link to reply.</p>
                </div>
            @else
                <div class="card p-6 text-sm text-slate-400">This conversation is closed. Reopen it to send another reply.</div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="card p-6 text-sm space-y-4">
                <div>
                    <p class="text-slate-500">Customer</p>
                    <p class="text-white font-medium mt-1">{{ $ticket->user?->name }}</p>
                    <p class="text-slate-400 break-all">{{ $ticket->user?->email }}</p>
                    @if ($ticket->user)
                        <a href="{{ route('admin.users.show', $ticket->user) }}" class="text-violet-400 hover:text-violet-300 text-xs mt-2 inline-block">View account</a>
                    @endif
                </div>

                @if ($ticket->download_id)
                    <div>
                        <p class="text-slate-500">Download</p>
                        <a href="{{ route('admin.downloads.show', $ticket->download_id) }}" class="text-violet-400 hover:text-violet-300 mt-1 inline-block">#{{ $ticket->download_id }}</a>
                        @if ($ticket->download)
                            <p class="text-xs text-slate-500 mt-1">{{ $ticket->download->platform?->name ?? 'Unknown' }} · {{ ucfirst($ticket->download->status) }}</p>
                        @endif
                    </div>
                @endif

                <div>
                    <p class="text-slate-500">Ticket status</p>
                    <p class="text-slate-300 mt-1">{{ $ticket->isOpen() ? 'Open' : 'Closed' }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection
