<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — {{ config('seo.site_name', config('app.name', 'Downloader')) }}</title>
    <link rel="icon" href="{{ asset('images/favicon.svg') }}" type="image/svg+xml">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950" x-data="{ sidebarOpen: false }">
    <div class="min-h-screen lg:flex" @keydown.escape.window="sidebarOpen = false">
        <div x-cloak x-show="sidebarOpen" class="fixed inset-0 z-40 lg:hidden" style="display: none;">
            <div
                x-show="sidebarOpen"
                x-transition.opacity
                class="absolute inset-0 bg-slate-950/85 backdrop-blur-md"
                @click="sidebarOpen = false"
            ></div>

            <aside
                x-show="sidebarOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="-translate-x-full opacity-0"
                x-transition:enter-end="translate-x-0 opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="translate-x-0 opacity-100"
                x-transition:leave-end="-translate-x-full opacity-0"
                class="absolute inset-y-0 left-0 flex w-72 max-w-[85vw] flex-col border-r border-slate-800 bg-slate-950 p-5 shadow-2xl shadow-slate-950/80"
                style="display: none;"
            >
                <div class="mb-8 flex items-center justify-between">
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 font-bold text-white">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-600 text-xs">A</span>
                        Admin
                    </a>
                    <button
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-700 bg-slate-950/80 text-slate-300 transition hover:border-slate-500 hover:text-white"
                        @click="sidebarOpen = false"
                        aria-label="Close sidebar"
                    >
                        <span class="text-lg leading-none">&times;</span>
                    </button>
                </div>

                <nav class="space-y-1 text-sm">
                    @foreach ([
                        ['admin.dashboard', 'Dashboard', null],
                        ['admin.downloads.index', 'Downloads', null],
                        ['admin.support-tickets.index', 'Support', $adminAwaitingTicketCount ?? 0],
                        ['admin.upgrade-requests.index', 'Upgrades', $adminPendingUpgradeCount ?? 0],
                        ['admin.users.index', 'Users', null],
                        ['admin.plans.index', 'Plans', null],
                        ['admin.platforms.index', 'Platforms', null],
                        ['admin.settings.edit', 'Settings', null],
                    ] as [$route, $label, $badgeCount])
                        <a
                            href="{{ route($route) }}"
                            class="flex items-center justify-between rounded-lg px-3 py-2 {{ request()->routeIs(str_replace('.index', '.*', $route)) || request()->routeIs($route) ? 'bg-violet-600/20 text-violet-300' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                            @click="sidebarOpen = false"
                        >
                            <span>{{ $label }}</span>
                            @if ($badgeCount)
                                <span class="rounded-full bg-amber-500/20 px-2 py-0.5 text-xs font-semibold text-amber-300">{{ $badgeCount }}</span>
                            @endif
                        </a>
                    @endforeach
                    <a href="{{ route('home') }}" class="mt-4 block rounded-lg px-3 py-2 text-slate-500 hover:bg-slate-800 hover:text-white" @click="sidebarOpen = false">← Back to site</a>
                </nav>
            </aside>
        </div>

        <aside class="hidden w-64 shrink-0 border-r border-slate-800 bg-slate-900/50 p-5 lg:flex lg:flex-col">
            <a href="{{ route('admin.dashboard') }}" class="mb-8 flex items-center gap-2 font-bold text-white">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-600 text-xs">A</span>
                Admin
            </a>

            <nav class="space-y-1 text-sm">
                @foreach ([
                    ['admin.dashboard', 'Dashboard', null],
                    ['admin.downloads.index', 'Downloads', null],
                    ['admin.support-tickets.index', 'Support', $adminAwaitingTicketCount ?? 0],
                    ['admin.upgrade-requests.index', 'Upgrades', $adminPendingUpgradeCount ?? 0],
                    ['admin.users.index', 'Users', null],
                    ['admin.plans.index', 'Plans', null],
                    ['admin.platforms.index', 'Platforms', null],
                    ['admin.settings.edit', 'Settings', null],
                ] as [$route, $label, $badgeCount])
                    <a
                        href="{{ route($route) }}"
                        class="flex items-center justify-between rounded-lg px-3 py-2 {{ request()->routeIs(str_replace('.index', '.*', $route)) || request()->routeIs($route) ? 'bg-violet-600/20 text-violet-300' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                    >
                        <span>{{ $label }}</span>
                        @if ($badgeCount)
                            <span class="rounded-full bg-amber-500/20 px-2 py-0.5 text-xs font-semibold text-amber-300">{{ $badgeCount }}</span>
                        @endif
                    </a>
                @endforeach
                <a href="{{ route('home') }}" class="mt-4 block rounded-lg px-3 py-2 text-slate-500 hover:bg-slate-800 hover:text-white">← Back to site</a>
            </nav>
        </aside>

        <main class="min-w-0 flex-1">
            <div class="flex items-center justify-between border-b border-slate-800/80 px-4 py-4 sm:px-6 lg:hidden">
                <div>
                    <div class="text-xs uppercase tracking-[0.2em] text-slate-500">Admin</div>
                    <div class="text-sm font-semibold text-white">@yield('title', 'Admin')</div>
                </div>
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-700 bg-slate-900/80 px-3 py-2 text-sm font-medium text-slate-200 transition hover:border-slate-500 hover:bg-slate-800"
                    @click="sidebarOpen = true"
                    aria-label="Open sidebar"
                >
                    <span class="text-base leading-none">☰</span>
                    Menu
                </button>
            </div>

            <div class="overflow-x-hidden px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                @if (session('status'))
                    <div class="mb-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-400">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="mb-4 rounded-xl border border-red-500/20 bg-red-500/10 px-4 py-3 text-sm text-red-400">
                        <ul class="list-disc list-inside">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
