<div class="card p-6 sm:p-8 shadow-2xl shadow-violet-500/5" x-data="downloader()" x-init="restoreState()">
    @if (session('quota_notice'))
        <div class="mb-5 rounded-2xl border border-amber-500/20 bg-amber-500/10 px-4 py-4 text-sm text-amber-200">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="font-semibold text-amber-300">Monthly limit reached</p>
                    <p class="mt-1 text-amber-100/90">{{ session('quota_notice') }}</p>
                </div>
                @auth
                    <a href="{{ route('account') }}" class="btn-secondary !px-4 !py-2 text-xs">View my account</a>
                @else
                    <a href="{{ route('register') }}" class="btn-secondary !px-4 !py-2 text-xs">Create account</a>
                @endauth
            </div>
        </div>
    @endif

    <label class="label-dark mb-3">Paste a video or file link</label>
    <div class="flex flex-col sm:flex-row gap-3">
        <input
            type="url"
            x-model="url"
            @keydown.enter="analyze()"
            placeholder="https://youtube.com/watch?v=... — YouTube, TikTok, X, or direct file URL"
            class="input-dark flex-1 !py-3.5 !text-base"
        >
        <button type="button" @click="analyze()" :disabled="loading" class="btn-primary !py-3.5 shrink-0">
            <span x-show="!loading">Analyze link</span>
            <span x-show="loading" class="flex items-center gap-2">
                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                Analyzing…
            </span>
        </button>
    </div>

    <p x-show="error" x-text="error" x-cloak class="mt-4 text-sm text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-2.5"></p>

    <template x-if="metadata">
        <div class="mt-8 pt-8 border-t border-slate-800 space-y-6">
            <div class="flex gap-4 items-start">
                <img x-show="metadata.thumbnail" :src="metadata.thumbnail" alt="" class="w-28 h-20 object-cover rounded-xl border border-slate-700 shrink-0">
                <div class="min-w-0">
                    <h3 class="font-semibold text-white text-lg leading-snug" x-text="metadata.title"></h3>
                    <p class="text-sm text-slate-500 mt-1">Select a format below</p>
                </div>
            </div>

            <div class="space-y-2">
                <template x-for="format in metadata.formats" :key="format.id">
                    <label
                        class="flex items-center gap-3 rounded-xl border px-4 py-3 cursor-pointer transition"
                        :class="selectedFormat === formatValue(format) ? 'border-violet-500 bg-violet-500/10' : 'border-slate-700 hover:border-slate-600 hover:bg-slate-800/50'"
                        @click="selectedFormat = formatValue(format)"
                    >
                        <input type="radio" name="format_pick" :value="formatValue(format)" x-model="selectedFormat" class="text-violet-600 focus:ring-violet-500 bg-slate-900 border-slate-600">
                        <span class="text-sm font-medium text-slate-200" x-text="format.label"></span>
                    </label>
                </template>
            </div>

            @auth
                <form method="POST" action="{{ route('downloads.store') }}">
                    @csrf
                    <input type="hidden" name="url" :value="url">
                    <input type="hidden" name="title" :value="metadata.title">
                    <input type="hidden" name="platform_slug" :value="metadata.platform_slug">
                    <input type="hidden" name="format" :value="selectedFormat">
                    <button type="submit" :disabled="!selectedFormat" class="btn-primary w-full !py-3.5 disabled:opacity-40">
                        Start download
                    </button>
                </form>
                <p class="text-center text-xs text-slate-500">Your file will appear in <a href="{{ route('account') }}" class="text-violet-400 hover:underline">My account</a> when ready.</p>
            @else
                <button type="button" @click="promptSignIn()" :disabled="!selectedFormat" class="btn-primary w-full !py-3.5 disabled:opacity-40">
                    Sign in to download
                </button>
                <p class="text-center text-sm text-slate-500">
                    Free account required.
                    <a href="{{ route('register') }}" class="text-violet-400 font-medium hover:underline">Create one</a>
                    or
                    <a href="{{ route('login') }}" class="text-violet-400 font-medium hover:underline">log in</a>
                </p>
            @endauth
        </div>
    </template>
</div>

@push('scripts')
<script>
    function downloader() {
        return {
            url: '',
            loading: false,
            error: '',
            metadata: null,
            selectedFormat: null,
            isAuth: @json(auth()->check()),

            formatValue(format) {
                return `${format.id}|${format.ext || 'bin'}`;
            },

            saveState() {
                sessionStorage.setItem('pendingDownload', JSON.stringify({
                    url: this.url,
                    metadata: this.metadata,
                    selectedFormat: this.selectedFormat,
                }));
            },

            restoreState() {
                const raw = sessionStorage.getItem('pendingDownload');
                if (!raw) return;
                try {
                    const data = JSON.parse(raw);
                    this.url = data.url || '';
                    this.metadata = data.metadata || null;
                    this.selectedFormat = data.selectedFormat || null;
                    if (this.isAuth && this.metadata && this.selectedFormat) {
                        sessionStorage.removeItem('pendingDownload');
                    }
                } catch (e) {}
            },

            promptSignIn() {
                if (!this.selectedFormat) {
                    this.error = 'Please select a format first.';
                    return;
                }
                this.saveState();
                window.location.href = '{{ route('register') }}';
            },

            async analyze() {
                this.error = '';
                this.metadata = null;
                this.selectedFormat = null;

                if (!this.url) {
                    this.error = 'Please enter a URL.';
                    return;
                }

                this.loading = true;

                try {
                    const response = await fetch('{{ route('analyze') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ url: this.url }),
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        this.error = data.message || 'Could not analyze this URL.';
                        return;
                    }

                    this.metadata = data;
                    if (data.formats?.length >= 1) {
                        this.selectedFormat = this.formatValue(data.formats[0]);
                    }
                } catch (e) {
                    this.error = 'Something went wrong. Please try again.';
                } finally {
                    this.loading = false;
                }
            },
        };
    }
</script>
<style>[x-cloak] { display: none !important; }</style>
@endpush
