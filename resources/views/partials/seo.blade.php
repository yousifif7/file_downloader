@php
    $siteName = config('seo.site_name');
    $pageTitle = trim($__env->yieldContent('title'));
    $fullTitle = $pageTitle !== '' && $pageTitle !== 'Home'
        ? "{$pageTitle} — {$siteName}"
        : "{$siteName} — ".config('seo.tagline');
    $description = trim($__env->yieldContent('meta_description')) ?: config('seo.description');
    $canonical = trim($__env->yieldContent('canonical')) ?: url()->current();
    $robots = trim($__env->yieldContent('robots')) ?: 'index, follow';
    $ogImage = config('seo.og_image');
    $keywords = config('seo.keywords');
@endphp

<title>{{ $fullTitle }}</title>
<meta name="description" content="{{ $description }}">
@if ($keywords)
    <meta name="keywords" content="{{ $keywords }}">
@endif
<meta name="robots" content="{{ $robots }}">
<link rel="canonical" href="{{ $canonical }}">

<link rel="icon" href="{{ asset('images/favicon.svg') }}" type="image/svg+xml">
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/favicon.png') }}">
<link rel="apple-touch-icon" href="{{ asset('images/logo.png') }}">

<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:title" content="{{ $fullTitle }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:locale" content="{{ str_replace('_', '-', app()->getLocale()) }}">
@if (config('seo.og_image'))
    <meta property="og:image" content="{{ $ogImage }}">
@endif

<meta name="twitter:card" content="{{ config('seo.og_image') ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $fullTitle }}">
<meta name="twitter:description" content="{{ $description }}">
@if (config('seo.og_image'))
    <meta name="twitter:image" content="{{ $ogImage }}">
@endif

<meta name="application-name" content="{{ $siteName }}">
<meta name="theme-color" content="#020617">

@stack('structured_data')
