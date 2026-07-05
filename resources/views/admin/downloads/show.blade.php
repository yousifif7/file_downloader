@extends('layouts.admin')

@section('title', 'Download #'.$download->id)

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-2xl font-semibold text-white">Download #{{ $download->id }}</h1>
        @if (in_array($download->status, ['failed', 'expired']))
            <form method="POST" action="{{ route('admin.downloads.retry', $download) }}">
                @csrf
                <button class="btn-secondary justify-center sm:justify-start">Retry download</button>
            </form>
        @endif
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(18rem,1fr)]">
        <div class="card p-6 space-y-3 text-sm text-slate-200">
            <div><span class="text-slate-500 w-32 inline-block">Status</span> {{ ucfirst($download->status) }}</div>
            <div><span class="text-slate-500 w-32 inline-block">Failure type</span> {{ $download->status === 'failed' ? $failureLabel : '—' }}</div>
            <div><span class="text-slate-500 w-32 inline-block">User</span> {{ $download->user?->email ?? '—' }}</div>
            <div><span class="text-slate-500 w-32 inline-block">Platform</span> {{ $download->platform?->name ?? '—' }}</div>
            <div><span class="text-slate-500 w-32 inline-block">Format</span> {{ $download->format ?? '—' }}</div>
            <div><span class="text-slate-500 w-32 inline-block">Title</span> {{ $download->media_title ?? '—' }}</div>
            <div><span class="text-slate-500 w-32 inline-block">URL</span> <span class="break-all text-slate-300">{{ $download->source_url }}</span></div>
            <div><span class="text-slate-500 w-32 inline-block">File path</span> <span class="break-all text-slate-300">{{ $download->file_path ?? '—' }}</span></div>
            <div><span class="text-slate-500 w-32 inline-block">File size</span> {{ $download->file_size_bytes ? number_format($download->file_size_bytes / 1024 / 1024, 2).' MB' : '—' }}</div>
            <div><span class="text-slate-500 w-32 inline-block">IP</span> {{ $download->ip_address ?? '—' }}</div>
            <div><span class="text-slate-500 w-32 inline-block">Created</span> {{ $download->created_at }}</div>
            <div><span class="text-slate-500 w-32 inline-block">Updated</span> {{ $download->updated_at }}</div>
            <div><span class="text-slate-500 w-32 inline-block">Completed</span> {{ $download->completed_at ?? '—' }}</div>
            <div><span class="text-slate-500 w-32 inline-block">Expires</span> {{ $download->expires_at ?? '—' }}</div>

            @if ($download->error_message)
                <div class="pt-4">
                    <div class="text-slate-500 mb-2">Raw error</div>
                    <pre class="overflow-x-auto rounded-xl border border-red-500/20 bg-red-500/10 p-4 text-xs whitespace-pre-wrap break-words text-red-200">{{ $download->error_message }}</pre>
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="card p-6">
                <h2 class="mb-3 text-sm font-semibold text-white">Support checklist</h2>
                <ul class="space-y-2 text-sm text-slate-400">
                    <li>Status: <span class="text-slate-200">{{ ucfirst($download->status) }}</span></li>
                    <li>Platform: <span class="text-slate-200">{{ $download->platform?->name ?? 'Unknown' }}</span></li>
                    <li>Failure bucket: <span class="text-slate-200">{{ $download->status === 'failed' ? $failureLabel : '—' }}</span></li>
                    <li>File generated: <span class="text-slate-200">{{ $download->file_path ? 'Yes' : 'No' }}</span></li>
                    <li>User attached: <span class="text-slate-200">{{ $download->user ? 'Yes' : 'No' }}</span></li>
                </ul>
            </div>

            <div class="card p-6">
                <h2 class="mb-3 text-sm font-semibold text-white">Suggested next step</h2>
                <p class="text-sm text-slate-400">
                    @if ($download->status === 'failed' && $failureType === 'cookies')
                        Refresh the cookies for this platform in admin settings, then retry this download.
                    @elseif ($download->status === 'failed' && $failureType === 'ffmpeg')
                        Verify FFmpeg is present on the server and retry after confirming media tooling is healthy.
                    @elseif ($download->status === 'failed' && $failureType === 'deno')
                        Re-check the Deno runtime on the server before retrying this request.
                    @elseif ($download->status === 'failed')
                        Review the raw error, confirm extractor/runtime health, then retry if the failure looks temporary.
                    @elseif ($download->status === 'expired')
                        Requeue the download if the user still needs the file.
                    @else
                        This download does not need support intervention right now.
                    @endif
                </p>
            </div>
        </div>
    </div>
@endsection
