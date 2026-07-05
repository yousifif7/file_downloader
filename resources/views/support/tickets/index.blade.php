@extends('layouts.site')

@section('title', 'Support')

@section('content')
    <div class="max-w-4xl mx-auto px-4 sm:px-6 py-10 sm:py-14">
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-white">Support</h1>
                <p class="text-slate-400 mt-2">Your conversations with our team.</p>
            </div>
            <a href="{{ route('support.tickets.create') }}" class="btn-primary justify-center">New conversation</a>
        </div>

        <div class="card overflow-hidden">
            @if ($tickets->isEmpty())
                <div class="px-6 py-16 text-center">
                    <p class="text-slate-400">No support conversations yet.</p>
                    <a href="{{ route('support.tickets.create') }}" class="btn-primary mt-6 inline-flex">Start a conversation</a>
                </div>
            @else
                <div class="divide-y divide-slate-800">
                    @foreach ($tickets as $ticket)
                        <a href="{{ route('support.tickets.show', $ticket) }}" class="flex items-center gap-4 px-6 py-4 hover:bg-slate-800/40 transition">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <p class="font-medium text-slate-200 truncate">{{ $ticket->subject }}</p>
                                    @if ($ticket->hasUnreadForUser())
                                        <span class="shrink-0 rounded-full bg-violet-500/20 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-violet-300">New reply</span>
                                    @endif
                                    @if ($ticket->isClosed())
                                        <span class="shrink-0 rounded-full bg-slate-500/10 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Closed</span>
                                    @endif
                                </div>
                                <p class="text-xs text-slate-500 mt-1">
                                    #{{ $ticket->id }} · {{ $ticket->messages_count }} {{ str('message')->plural($ticket->messages_count) }}
                                    · {{ $ticket->last_message_at?->diffForHumans() ?? $ticket->created_at->diffForHumans() }}
                                </p>
                            </div>
                            <span class="text-slate-500">→</span>
                        </a>
                    @endforeach
                </div>
                @if ($tickets->hasPages())
                    <div class="px-6 py-4 border-t border-slate-800">{{ $tickets->links() }}</div>
                @endif
            @endif
        </div>
    </div>
@endsection
