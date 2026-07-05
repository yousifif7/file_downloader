<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title') — {{ config('seo.site_name', config('app.name', 'Downloader')) }}</title>
    <link rel="icon" href="{{ asset('images/favicon.svg') }}" type="image/svg+xml">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950">
    <div class="min-h-screen flex">
        {{-- Left panel --}}
        <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden bg-gradient-to-br from-violet-950 via-slate-950 to-fuchsia-950 p-12 flex-col justify-between">
            <div class="pointer-events-none absolute inset-0 bg-grid-pattern bg-[length:24px_24px] opacity-50"></div>
            <a href="{{ route('home') }}" class="relative flex items-center gap-2.5 font-bold text-lg text-white">
                <img src="{{ asset('images/logo.svg') }}" alt="{{ config('seo.site_name', config('app.name', 'Downloader')) }}" width="36" height="36" class="h-9 w-9 shrink-0 shadow-lg">
                {{ config('seo.site_name', config('app.name', 'Downloader')) }}
            </a>
            <div class="relative">
                <h2 class="text-3xl font-bold text-white leading-tight">Download anything.<br>Track everything.</h2>
                <p class="mt-4 text-slate-400 leading-relaxed max-w-md">Create a free account to save your downloads, track your monthly quota, and access files anytime from your dashboard.</p>
            </div>
            <p class="relative text-sm text-slate-600">&copy; {{ date('Y') }} {{ config('seo.site_name', config('app.name', 'Downloader')) }} · <a href="{{ config('seo.parent_site_url') }}" target="_blank" rel="noopener noreferrer" class="text-violet-400/80 hover:text-violet-300 transition">{{ config('seo.parent_site_name') }}</a></p>
        </div>

        {{-- Right panel --}}
        <div class="flex-1 flex flex-col justify-center px-6 py-12 sm:px-12 lg:px-16">
            <div class="lg:hidden mb-8">
                <a href="{{ route('home') }}" class="flex items-center gap-2 font-bold text-white">
                    <img src="{{ asset('images/logo.svg') }}" alt="{{ config('seo.site_name', config('app.name', 'Downloader')) }}" width="32" height="32" class="h-8 w-8 shrink-0">
                    {{ config('seo.site_name', config('app.name', 'Downloader')) }}
                </a>
            </div>

            <div class="w-full max-w-md mx-auto">
                <h1 class="text-2xl font-bold text-white mb-2">@yield('heading')</h1>
                @hasSection('subheading')
                    <p class="text-slate-400 text-sm mb-8">@yield('subheading')</p>
                @endif

                @if (session('status'))
                    <div class="mb-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 px-4 py-3 text-sm">{{ session('status') }}</div>
                @endif

                @yield('content')
            </div>
        </div>
    </div>
</body>
</html>
