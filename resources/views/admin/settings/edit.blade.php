@extends('layouts.admin')

@section('title', 'Settings')

@section('content')
    <h1 class="text-2xl font-semibold text-white mb-6">Settings</h1>

    <form method="POST" action="{{ route('admin.settings.update') }}" class="card p-6 max-w-xl space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="label-dark mb-1">Free tier monthly limit</label>
            <input type="number" name="free_tier_limit" value="{{ old('free_tier_limit', $settings['free_tier_limit']) }}" class="admin-input" min="1" required>
            <p class="text-xs text-slate-500 mt-1">Legacy fallback only. Edit the Free plan under Plans for the live monthly limit shown to users.</p>
        </div>

        <div>
            <label class="label-dark mb-1">Max file size (MB)</label>
            <input type="number" name="max_file_size_mb" value="{{ old('max_file_size_mb', $settings['max_file_size_mb']) }}" class="admin-input" min="1" required>
        </div>

        <div>
            <label class="label-dark mb-1">Download link TTL (hours)</label>
            <input type="number" name="download_ttl_hours" value="{{ old('download_ttl_hours', $settings['download_ttl_hours']) }}" class="admin-input" min="1" required>
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-300">
            <input type="checkbox" name="maintenance_mode" value="1" class="admin-checkbox" @checked(old('maintenance_mode', $settings['maintenance_mode']))>
            Maintenance mode
        </label>

        <div>
            <label class="label-dark mb-1">Maintenance message</label>
            <textarea name="maintenance_message" rows="3" class="admin-input">{{ old('maintenance_message', $settings['maintenance_message']) }}</textarea>
        </div>

        <button class="btn-primary">Save settings</button>
    </form>

    <div class="mt-8 space-y-6">
        <div class="max-w-3xl">
            <h2 class="text-lg font-semibold text-white">Platform cookies</h2>
            <p class="mt-2 text-sm text-slate-400">
                Upload separate `cookies.txt` files for platforms that require an authenticated browser session. Each platform uses its own file, so refreshing Instagram cookies will not overwrite YouTube or LinkedIn.
            </p>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            @foreach ($platformCookies as $platform => $cookieStatus)
                <div class="card p-6 space-y-4">
                    <div>
                        <h3 class="text-base font-semibold text-white">{{ $cookieStatus['name'] }}</h3>
                        <p class="mt-1 text-xs text-slate-500 break-all">{{ $cookieStatus['path'] }}</p>
                    </div>

                    @if ($cookieStatus['exists'])
                        <p class="text-sm text-emerald-400">
                            Cookies file active
                            @if ($cookieStatus['updated_at'])
                                (updated {{ \Illuminate\Support\Carbon::createFromTimestamp($cookieStatus['updated_at'])->diffForHumans() }})
                            @endif
                        </p>
                    @else
                        <p class="text-sm text-amber-400">No cookies file uploaded yet for {{ $cookieStatus['name'] }}.</p>
                    @endif

                    <form method="POST" action="{{ route('admin.settings.platform-cookies', $platform) }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <div>
                            <label class="label-dark mb-1">Upload {{ $cookieStatus['name'] }} cookies.txt</label>
                            <input type="file" name="platform_cookies" accept=".txt" class="admin-input" required>
                            @error('platform_cookies')
                                <p class="text-sm text-red-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <p class="text-xs text-slate-500">
                            Export a Netscape `cookies.txt` file from a browser that is logged into {{ $cookieStatus['name'] }}.
                        </p>
                        <button class="btn-primary">Upload {{ $cookieStatus['name'] }} cookies</button>
                    </form>
                </div>
            @endforeach
        </div>
    </div>
@endsection
