<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.seo')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950">
    <div class="pointer-events-none fixed inset-0 bg-hero-glow"></div>
    <div class="pointer-events-none fixed inset-0 bg-grid-pattern bg-[length:24px_24px]"></div>

    @include('partials.nav')

    <main>
        @yield('content')
    </main>

    <footer class="relative border-t border-slate-800/80 py-10 mt-20">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-slate-500">
                <p>&copy; {{ date('Y') }} {{ config('seo.site_name', config('app.name')) }}.
                    A <a href="{{ config('seo.parent_site_url') }}" target="_blank" rel="noopener noreferrer" class="text-violet-400 hover:text-violet-300 transition">{{ config('seo.parent_site_name') }}</a> product.
                </p>
                <nav class="flex flex-wrap items-center justify-center sm:justify-end gap-4" aria-label="Footer">
                    <a href="{{ route('support') }}" class="hover:text-slate-300 transition">Support</a>
                    <a href="{{ route('terms') }}" class="hover:text-slate-300 transition">Terms</a>
                    <a href="{{ route('privacy') }}" class="hover:text-slate-300 transition">Privacy</a>
                    <a href="{{ route('refund') }}" class="hover:text-slate-300 transition">Refunds</a>
                </nav>
            </div>
            <p class="text-center text-xs text-slate-600 mt-4">Use responsibly. Only download content you have rights to.</p>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
