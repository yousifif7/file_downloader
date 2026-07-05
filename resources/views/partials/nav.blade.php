<nav class="relative z-50 border-b border-slate-800/60 bg-slate-950/70 backdrop-blur-xl" x-data="{ mobileMenuOpen: false }">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-4">
        <a href="{{ route('home') }}" class="min-w-0 flex items-center gap-2.5 font-bold text-lg tracking-tight">
            <img src="{{ asset('images/logo.svg') }}" alt="{{ config('seo.site_name', config('app.name', 'Downloader')) }}" width="36" height="36" class="h-9 w-9 shrink-0 shadow-lg shadow-violet-500/25">
            <span class="truncate">{{ config('seo.site_name', config('app.name', 'Downloader')) }}</span>
        </a>

        <div class="hidden sm:flex items-center gap-8 text-sm font-medium text-slate-400">
            <a href="{{ route('home') }}#download" class="hover:text-white transition">Downloader</a>
            <a href="{{ route('home') }}#how-it-works" class="hover:text-white transition">How it works</a>
            <a href="{{ route('home') }}#faq" class="hover:text-white transition">FAQ</a>
            <a href="{{ route('home') }}#pricing" class="hover:text-white transition">Pricing</a>
            <a href="{{ auth()->check() ? route('support.tickets.index') : route('support') }}" class="hover:text-white transition">Support</a>
        </div>

        <div class="hidden sm:flex items-center gap-3">
            @auth
                <a href="{{ route('account') }}" class="text-sm font-medium text-slate-300 hover:text-white transition">My account</a>
                @if (Auth::user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="text-sm font-medium text-violet-400 hover:text-violet-300 transition">Admin</a>
                @endif
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="btn-secondary !px-4 !py-2 text-xs">Log out</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="text-sm font-medium text-slate-300 hover:text-white transition">Log in</a>
                <a href="{{ route('register') }}" class="btn-primary !px-4 !py-2 text-xs">Sign up free</a>
            @endauth
        </div>

        <button
            type="button"
            class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-700 bg-slate-900/80 text-slate-200 transition hover:border-slate-500 hover:bg-slate-800 sm:hidden"
            @click="mobileMenuOpen = !mobileMenuOpen"
            :aria-expanded="mobileMenuOpen.toString()"
            aria-label="Toggle navigation"
        >
            <span x-show="!mobileMenuOpen" aria-hidden="true">☰</span>
            <span x-show="mobileMenuOpen" x-cloak aria-hidden="true">&times;</span>
        </button>
    </div>

    <div x-cloak x-show="mobileMenuOpen" x-transition.opacity class="border-t border-slate-800/60 sm:hidden">
        <div class="max-w-6xl mx-auto space-y-4 px-4 py-4">
            <div class="space-y-1 text-sm font-medium text-slate-300">
                <a href="{{ route('home') }}#download" class="block rounded-xl px-3 py-2 transition hover:bg-slate-900 hover:text-white" @click="mobileMenuOpen = false">Downloader</a>
                <a href="{{ route('home') }}#how-it-works" class="block rounded-xl px-3 py-2 transition hover:bg-slate-900 hover:text-white" @click="mobileMenuOpen = false">How it works</a>
                <a href="{{ route('home') }}#faq" class="block rounded-xl px-3 py-2 transition hover:bg-slate-900 hover:text-white" @click="mobileMenuOpen = false">FAQ</a>
                <a href="{{ route('home') }}#pricing" class="block rounded-xl px-3 py-2 transition hover:bg-slate-900 hover:text-white" @click="mobileMenuOpen = false">Pricing</a>
                <a href="{{ auth()->check() ? route('support.tickets.index') : route('support') }}" class="block rounded-xl px-3 py-2 transition hover:bg-slate-900 hover:text-white" @click="mobileMenuOpen = false">Support</a>
            </div>

            <div class="flex flex-col gap-2 border-t border-slate-800/60 pt-4">
                @auth
                    <a href="{{ route('account') }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-slate-200 transition hover:bg-slate-900 hover:text-white" @click="mobileMenuOpen = false">My account</a>
                    @if (Auth::user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-violet-300 transition hover:bg-slate-900 hover:text-violet-200" @click="mobileMenuOpen = false">Admin</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn-secondary w-full justify-center">Log out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-slate-200 transition hover:bg-slate-900 hover:text-white" @click="mobileMenuOpen = false">Log in</a>
                    <a href="{{ route('register') }}" class="btn-primary w-full justify-center" @click="mobileMenuOpen = false">Sign up free</a>
                @endauth
            </div>
        </div>
    </div>
</nav>
