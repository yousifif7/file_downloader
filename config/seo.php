<?php

return [

    'site_name' => env('SEO_SITE_NAME', env('APP_NAME', 'Downloader')),

    'tagline' => env('SEO_TAGLINE', 'Free Online Video Downloader'),

    'description' => env(
        'SEO_DESCRIPTION',
        'Download videos from YouTube, TikTok, Twitter/X, and direct file links. Paste a URL, choose MP4 or audio quality, and save to your device. Free account — 3 downloads per month.'
    ),

    'keywords' => env(
        'SEO_KEYWORDS',
        'youtube downloader, download youtube video, youtube to mp4, tiktok downloader, download tiktok video, twitter video downloader, x video download, save video online, online video downloader, mp4 downloader, free video downloader, video link downloader'
    ),

    'parent_site_url' => env('SEO_PARENT_URL', 'https://yousiffarra.com'),

    'parent_site_name' => env('SEO_PARENT_NAME', 'Yousif ElFarra'),

    'og_image' => env('SEO_OG_IMAGE', null),

];
