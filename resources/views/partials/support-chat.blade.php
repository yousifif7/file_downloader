@props(['messages'])

<div class="space-y-4">
    @foreach ($messages as $message)
        <div @class([
            'flex',
            'justify-end' => ! $message->is_staff,
            'justify-start' => $message->is_staff,
        ])>
            <div @class([
                'max-w-[85%] rounded-2xl px-4 py-3 text-sm leading-relaxed',
                'bg-violet-600/20 border border-violet-500/20 text-slate-100' => ! $message->is_staff,
                'bg-slate-800/80 border border-slate-700 text-slate-200' => $message->is_staff,
            ])>
                <div class="mb-1 flex items-center gap-2 text-xs">
                    <span @class([
                        'font-semibold',
                        'text-violet-300' => ! $message->is_staff,
                        'text-slate-300' => $message->is_staff,
                    ])>
                        {{ $message->authorName() }}
                    </span>
                    <span class="text-slate-500">{{ $message->created_at->format('M j, g:i A') }}</span>
                </div>
                <div class="whitespace-pre-wrap">{{ $message->body }}</div>
            </div>
        </div>
    @endforeach
</div>
