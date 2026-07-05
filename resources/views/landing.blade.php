@extends('layouts.site')

@php
    $siteName = config('seo.site_name', config('app.name'));
    $freeLimitLabel = $catalog->monthlyLimitLabel($freePlan);
    $freePlatformLabel = $catalog->platformNamesLabel($freePlan);
    $enabledPlatformLabel = $catalog->enabledPlatformNamesLabel($platforms);
    $faqs = [
        [
            'q' => 'How do I download a YouTube video to MP4?',
            'a' => 'Paste your YouTube link into the box above, click Analyze link, choose an MP4 quality (360p is recommended for reliability), then sign in to start the download. Your file appears in My account when ready.',
        ],
        [
            'q' => 'Is this YouTube downloader free?',
            'a' => 'Yes. Free accounts get '.$freeLimitLabel.' on '.$freePlatformLabel.'. Paid plans with higher limits and more platforms are available now — see Plans & pricing below or upgrade from your account.',
        ],
        [
            'q' => 'Which sites are supported?',
            'a' => 'We support '.$enabledPlatformLabel.'. Some platforms require a paid plan — each one is labeled in the Supported platforms section below, and you can compare plans in pricing.',
        ],
        [
            'q' => 'Do I need to install software?',
            'a' => 'No. '.$siteName.' runs entirely in your browser. Paste a URL, pick a format, and download — no extensions or desktop apps required.',
        ],
        [
            'q' => 'Is it legal to download videos?',
            'a' => 'You are responsible for the content you download. Only save videos you own or have explicit permission to download, and respect the terms of the source platform and copyright law.',
        ],
    ];
@endphp

@section('title', 'Home')

@section('meta_description', $catalog->seoDescription($freePlan))

@section('canonical', route('home'))

@section('content')
    {{-- Hero --}}
    <section class="relative pt-16 pb-8 sm:pt-24 sm:pb-12">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 text-center">
            <div class="inline-flex items-center gap-2 rounded-full border border-violet-500/30 bg-violet-500/10 px-4 py-1.5 text-xs font-medium text-violet-300 mb-6">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                Free online video downloader — {{ $freeLimitLabel }}
            </div>
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-white max-w-4xl mx-auto leading-[1.1]">
                Free <span class="bg-gradient-to-r from-violet-400 to-fuchsia-400 bg-clip-text text-transparent">YouTube &amp; video downloader</span> — save MP4 online
            </h1>
            <p class="mt-6 text-lg sm:text-xl text-slate-400 max-w-2xl mx-auto leading-relaxed">
                Paste a YouTube, TikTok, Twitter/X, or direct file URL. Preview formats instantly, choose MP4 or audio quality, and download to your device after a quick free sign-up.
            </p>
        </div>
    </section>

    {{-- Downloader tool --}}
    <section class="relative pb-16" id="download" aria-label="Video downloader tool">
        <div class="max-w-3xl mx-auto px-4 sm:px-6">
            @include('partials.downloader-tool')
        </div>
    </section>

    {{-- Features --}}
    <section class="relative py-16 border-t border-slate-800/60" id="features">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <h2 class="text-3xl font-bold text-white text-center mb-4">Why use {{ $siteName }}?</h2>
            <p class="text-slate-400 text-center max-w-2xl mx-auto mb-12">
                A fast, browser-based video link downloader built for everyday use — no clutter, no installs.
            </p>
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach ([
                    ['title' => 'YouTube to MP4', 'desc' => 'Download YouTube videos in MP4. Pick the quality that fits your device and connection.'],
                    ['title' => 'TikTok & social', 'desc' => 'Save TikTok and Twitter/X videos without watermark headaches when the platform allows.'],
                    ['title' => 'Direct file links', 'desc' => 'Grab files from any direct HTTP/HTTPS URL — documents, archives, media, and more.'],
                    ['title' => 'Account & history', 'desc' => 'Track downloads, check your monthly quota, and pick up files from one dashboard.'],
                ] as $feature)
                    <div class="card p-6">
                        <h3 class="text-base font-semibold text-white mb-2">{{ $feature['title'] }}</h3>
                        <p class="text-sm text-slate-400 leading-relaxed">{{ $feature['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="relative py-20 border-t border-slate-800/60" id="how-it-works">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <h2 class="text-3xl font-bold text-white text-center mb-4">How to download videos online</h2>
            <p class="text-slate-400 text-center max-w-xl mx-auto mb-12">Three simple steps. Works on phone, tablet, and desktop.</p>
            <div class="grid sm:grid-cols-3 gap-6">
                @foreach ([
                    ['step' => '01', 'title' => 'Paste your link', 'desc' => 'Copy a YouTube, TikTok, X, or direct file URL and paste it into the downloader above.'],
                    ['step' => '02', 'title' => 'Pick a format', 'desc' => 'We analyze the link and show available MP4 or audio options — choose the quality you want.'],
                    ['step' => '03', 'title' => 'Sign in & download', 'desc' => 'Create a free account to start the download. Files are ready in My account when processing finishes.'],
                ] as $item)
                    <div class="card p-6">
                        <div class="text-4xl font-black text-slate-800 mb-4">{{ $item['step'] }}</div>
                        <h3 class="text-lg font-semibold text-white mb-2">{{ $item['title'] }}</h3>
                        <p class="text-sm text-slate-400 leading-relaxed">{{ $item['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Platforms --}}
    <section class="relative py-20" id="platforms">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <h2 class="text-3xl font-bold text-white text-center mb-4">Supported platforms</h2>
            <p class="text-slate-400 text-center max-w-xl mx-auto mb-12">Platform access depends on your plan — badges show what is included on Free vs paid tiers.</p>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                @foreach ($platforms as $platform)
                    @php
                        $availability = $catalog->platformAvailability($platform, $freePlan, $plans);
                    @endphp
                    <div @class([
                        'card px-4 py-5 text-center text-sm font-medium',
                        'text-slate-300' => $availability['status'] !== 'coming_soon',
                        'text-slate-600 opacity-60' => $availability['status'] === 'coming_soon',
                    ])>
                        {{ $platform->name }}
                        @if ($availability['status'] === 'free')
                            <span class="block text-xs text-emerald-400/80 mt-1">Included in Free</span>
                        @elseif ($availability['status'] === 'paid')
                            <span class="block text-xs text-violet-400/80 mt-1">{{ $availability['plan']->name }} plan</span>
                        @else
                            <span class="block text-xs text-slate-600 mt-1">Coming soon</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="relative py-20 border-t border-slate-800/60" id="faq">
        <div class="max-w-3xl mx-auto px-4 sm:px-6">
            <h2 class="text-3xl font-bold text-white text-center mb-4">Frequently asked questions</h2>
            <p class="text-slate-400 text-center mb-10">Common questions about our free online video downloader.</p>
            <div class="space-y-4">
                @foreach ($faqs as $faq)
                    <details class="card group">
                        <summary class="cursor-pointer list-none p-5 font-medium text-white flex items-center justify-between gap-4">
                            <span>{{ $faq['q'] }}</span>
                            <span class="text-slate-500 group-open:rotate-180 transition-transform shrink-0" aria-hidden="true">▼</span>
                        </summary>
                        <div class="px-5 pb-5 text-sm text-slate-400 leading-relaxed border-t border-slate-800/80 pt-4">
                            {{ $faq['a'] }}
                        </div>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Pricing --}}
    @php
        $authUser = auth()->user();
        if ($authUser) {
            $authUser->loadMissing('plan');
        }
        $currentPlan = $authUser
            ? app(\App\Services\SubscriptionAccessService::class)->effectivePlanFor($authUser)
            : null;
    @endphp
    <section class="relative py-20 border-t border-slate-800/60" id="pricing">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-white mb-4">Plans &amp; pricing</h2>
                <p class="text-slate-400 max-w-2xl mx-auto leading-relaxed">
                    Start free today. Upgrade when you need more downloads or additional platforms — pay by bank transfer or crypto.
                </p>
            </div>

            <div @class([
                'grid gap-6 lg:gap-8 mx-auto items-stretch pb-8',
                'lg:grid-cols-3' => $plans->count() >= 3,
                'sm:grid-cols-2 max-w-4xl' => $plans->count() === 2,
                'max-w-md' => $plans->count() === 1,
            ])>
                @foreach ($plans as $plan)
                    @include('partials.plan-pricing-card', [
                        'plan' => $plan,
                        'catalog' => $catalog,
                        'plans' => $plans,
                        'currentPlan' => $currentPlan,
                    ])
                @endforeach
            </div>

            <div class="mt-16 lg:mt-20 max-w-4xl mx-auto">
                @include('partials.billing-trust')
            </div>
        </div>
    </section>
@endsection

@push('structured_data')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'WebSite',
            '@id' => route('home').'#website',
            'url' => route('home'),
            'name' => $siteName,
            'description' => config('seo.description'),
            'publisher' => [
                '@id' => config('seo.parent_site_url').'#organization',
            ],
        ],
        [
            '@type' => 'WebApplication',
            '@id' => route('home').'#app',
            'name' => $siteName,
            'url' => route('home'),
            'applicationCategory' => 'MultimediaApplication',
            'operatingSystem' => 'Web',
            'browserRequirements' => 'Requires JavaScript',
            'description' => config('seo.description'),
            'offers' => [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'USD',
            ],
            'featureList' => 'YouTube downloader, TikTok downloader, Twitter video download, direct file download, MP4 format selection',
        ],
        [
            '@type' => 'FAQPage',
            '@id' => route('home').'#faq',
            'mainEntity' => collect($faqs)->map(fn ($faq) => [
                '@type' => 'Question',
                'name' => $faq['q'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq['a'],
                ],
            ])->values()->all(),
        ],
        [
            '@type' => 'Organization',
            '@id' => config('seo.parent_site_url').'#organization',
            'name' => config('seo.parent_site_name'),
            'url' => config('seo.parent_site_url'),
        ],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
</script>
@endpush
