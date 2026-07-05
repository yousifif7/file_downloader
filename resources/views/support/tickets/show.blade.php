@extends('layouts.site')

@section('title', 'Support #'.$ticket->id)

@section('content')
    <div class="max-w-4xl mx-auto px-4 sm:px-6 py-10 sm:py-14">
        <a href="{{ route('support.tickets.index') }}" class="text-sm text-violet-400 hover:text-violet-300">← All conversations</a>

        <div class="mt-4 mb-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-white">{{ $ticket->subject }}</h1>
                <p class="text-sm text-slate-500 mt-1">
                    Conversation #{{ $ticket->id }}
                    @if ($ticket->download_id)
                        · Download <a href="{{ route('downloads.show', $ticket->download_id) }}" class="text-violet-400 hover:underline">#{{ $ticket->download_id }}</a>
                    @endif
                </p>
            </div>
            @if ($ticket->isClosed())
                <span class="inline-flex items-center rounded-full border border-slate-600 bg-slate-800 px-3 py-1 text-xs font-semibold text-slate-300">Closed</span>
            @else
                <span class="inline-flex items-center rounded-full border border-emerald-500/20 bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-300">Open</span>
            @endif
        </div>

        @if (session('status'))
            <div class="mb-6 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 px-4 py-3 text-sm">{{ session('status') }}</div>
        @endif

        <div class="card p-6 mb-6 min-h-[320px] max-h-[60vh] overflow-y-auto">
            @include('partials.support-chat', ['messages' => $ticket->messages])
        </div>

        @if ($ticket->isOpen())
            <div class="card p-6">
                <form method="POST" action="{{ route('support.tickets.messages.store', $ticket) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="message" class="label-dark">Your reply</label>
                        <textarea id="message" name="message" rows="4" required class="input-dark mt-1.5 min-h-[110px]" placeholder="Write your message...">{{ old('message') }}</textarea>
                        @error('message')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="btn-primary">Send message</button>
                </form>
            </div>
        @else
            <div class="card p-6 text-sm text-slate-400">
                This conversation is closed. If you still need help, <a href="{{ route('support.tickets.create') }}" class="text-violet-400 hover:underline">start a new conversation</a>.
            </div>
        @endif
    </div>
@endsection
