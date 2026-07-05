<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /account',
            'Disallow: /downloads/',
            'Disallow: /profile',
            '',
            'Sitemap: '.url('/sitemap.xml'),
        ];

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    public function sitemap(): Response
    {
        $urls = [
            [
                'loc' => route('home'),
                'changefreq' => 'weekly',
                'priority' => '1.0',
            ],
            [
                'loc' => route('terms'),
                'changefreq' => 'monthly',
                'priority' => '0.3',
            ],
            [
                'loc' => route('privacy'),
                'changefreq' => 'monthly',
                'priority' => '0.3',
            ],
            [
                'loc' => route('refund'),
                'changefreq' => 'monthly',
                'priority' => '0.3',
            ],
            [
                'loc' => route('support'),
                'changefreq' => 'monthly',
                'priority' => '0.4',
            ],
        ];

        return response()
            ->view('seo.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
