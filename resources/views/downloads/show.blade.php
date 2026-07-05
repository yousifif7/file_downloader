@extends('layouts.site')

@section('title', 'Download')

@section('content')
    <div class="max-w-2xl mx-auto px-4 sm:px-6 py-12"
         @if (! in_array($download->status, ['completed', 'failed', 'expired'])) x-data x-init="setInterval(() => window.location.reload(), 4000)" @endif>

        <a href="{{ route('account') }}" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-violet-400 transition mb-8">
            ← Back to account
        </a>

        <div class="card p-8">
            <div class="flex items-start justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-xl font-bold text-white">{{ $download->media_title ?: 'Your download' }}</h1>
                    <p class="text-sm text-slate-500 mt-1">{{ $download->platform?->name ?? 'File' }} · {{ $download->created_at->format('M j, Y g:i A') }}</p>
                </div>
                <span @class([
                    'text-xs font-semibold rounded-full px-3 py-1 shrink-0 capitalize',
                    'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' => $download->status === 'completed',
                    'bg-amber-500/10 text-amber-400 border border-amber-500/20' => in_array($download->status, ['pending', 'processing']),
                    'bg-red-500/10 text-red-400 border border-red-500/20' => $download->status === 'failed',
                    'bg-slate-500/10 text-slate-400 border border-slate-500/20' => ! in_array($download->status, ['completed', 'pending', 'processing', 'failed']),
                ])>
                    {{ $download->status }}
                </span>
            </div>

            <p class="text-sm text-slate-500 break-all mb-6">{{ $download->source_url }}</p>

            @if ($download->status === 'failed')
                <div class="rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 px-4 py-3 text-sm mb-6">
                    {{ $download->error_message ?: 'Download failed. Please try again.' }}
                </div>
                <div class="rounded-xl border border-slate-800 bg-slate-950/60 px-4 py-3 text-sm text-slate-400 mb-6">
                    <p class="font-medium text-slate-200">What to do next</p>
                    <p class="mt-1">Retry the same link from the home page in a few moments. If the same link keeps failing, try a different format or <a href="{{ auth()->check() ? route('support.tickets.create', ['download' => $download->id]) : route('login') }}" class="text-red-300 underline hover:text-white">contact support</a>@if (auth()->guest()) after signing in @endif with download #{{ $download->id }}.</p>
                </div>
                <a href="{{ route('home') }}#download" class="btn-primary">Try again</a>
            @elseif ($download->status === 'expired')
                <p class="text-slate-400 text-sm">This download has expired. Start a new one from the home page.</p>
                <a href="{{ route('home') }}#download" class="btn-secondary mt-4 inline-flex">New download</a>
            @elseif ($downloadUrl)
                <div class="space-y-4">
                    <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
                        Your file is ready. The download should start automatically in a moment.
                    </div>

                    <a
                        id="download-link"
                        href="{{ $downloadUrl }}"
                        class="btn-primary w-full !py-3.5 text-center"
                    >
                        Download file
                        @if ($download->file_size_bytes)
                            ({{ number_format($download->file_size_bytes / 1024 / 1024, 1) }} MB)
                        @endif
                    </a>

                    <p class="text-center text-xs text-slate-500">
                        If nothing happens, tap the button above to start the download manually.
                    </p>
                    <p class="text-center text-xs text-slate-600">Link expires {{ $download->expires_at?->diffForHumans() ?? 'soon' }}</p>
                </div>
            @elseif ($download->status === 'completed')
                <div class="rounded-xl border border-amber-500/20 bg-amber-500/10 px-4 py-3 text-sm text-amber-200 mb-4">
                    @if ($download->expires_at && $download->expires_at->isPast())
                        This download link has expired. Start a new download from the home page.
                    @elseif (! $download->file_path)
                        The file is no longer available on the server. Please start a new download.
                    @else
                        The file could not be served. Please try again or contact support with download #{{ $download->id }}.
                    @endif
                </div>
                <a href="{{ route('home') }}#download" class="btn-primary">Start a new download</a>
            @else
                <div class="flex items-center gap-3 text-slate-400 text-sm">
                    <svg class="animate-spin h-5 w-5 text-violet-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    Preparing your file… this page refreshes automatically.
                </div>
                <p class="mt-4 text-sm text-slate-500">Most downloads finish within a minute. If this page stays stuck for too long, return to your account and start a fresh attempt.</p>
            @endif
        </div>
    </div>
@endsection

@if (! empty($downloadUrl))
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const link = document.getElementById('download-link');
                if (!link) {
                    return;
                }

                window.setTimeout(function () {
                    link.click();
                }, 400);
            });
        </script>
    @endpush
@endif
