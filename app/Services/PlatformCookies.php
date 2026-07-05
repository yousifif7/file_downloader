<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use InvalidArgumentException;

class PlatformCookies
{
    /**
     * @return array<string, array{name: string, domains: list<string>, filename: string}>
     */
    public static function supportedPlatforms(): array
    {
        return [
            'youtube' => [
                'name' => 'YouTube',
                'domains' => ['youtube.com', '.youtube.com', 'youtu.be'],
                'filename' => 'yt-dlp-cookies-youtube.txt',
            ],
            'instagram' => [
                'name' => 'Instagram',
                'domains' => ['instagram.com', '.instagram.com'],
                'filename' => 'yt-dlp-cookies-instagram.txt',
            ],
            'facebook' => [
                'name' => 'Facebook',
                'domains' => ['facebook.com', '.facebook.com', 'fb.watch'],
                'filename' => 'yt-dlp-cookies-facebook.txt',
            ],
            'linkedin' => [
                'name' => 'LinkedIn',
                'domains' => ['linkedin.com', '.linkedin.com', 'lnkd.in'],
                'filename' => 'yt-dlp-cookies-linkedin.txt',
            ],
        ];
    }

    public static function assertSupported(string $platform): void
    {
        if (! array_key_exists($platform, self::supportedPlatforms())) {
            throw new InvalidArgumentException("Unsupported platform [{$platform}] for cookies.");
        }
    }

    public static function pathFor(string $platform): string
    {
        self::assertSupported($platform);

        return storage_path('app/'.self::supportedPlatforms()[$platform]['filename']);
    }

    public static function legacyFallbackPath(): ?string
    {
        $legacy = storage_path('app/yt-dlp-cookies.txt');

        return is_file($legacy) ? $legacy : null;
    }

    public static function resolveForUrl(string $url): ?string
    {
        $slug = app(UrlDetector::class)->detect($url);

        if (is_string($slug) && array_key_exists($slug, self::supportedPlatforms())) {
            $path = self::pathFor($slug);

            if (is_file($path)) {
                return $path;
            }
        }

        return self::legacyFallbackPath();
    }

    public static function validateContent(string $platform, string $content): bool
    {
        self::assertSupported($platform);

        if (trim($content) === '') {
            return false;
        }

        foreach (self::supportedPlatforms()[$platform]['domains'] as $domain) {
            if (str_contains($content, $domain)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, array{name: string, exists: bool, updated_at: int|null, path: string}>
     */
    public static function status(): array
    {
        $status = [];

        foreach (self::supportedPlatforms() as $platform => $config) {
            $path = self::pathFor($platform);

            $status[$platform] = [
                'name' => $config['name'],
                'exists' => is_file($path),
                'updated_at' => is_file($path) ? filemtime($path) : null,
                'path' => $path,
            ];
        }

        return $status;
    }

    public static function store(string $platform, string $content): void
    {
        File::ensureDirectoryExists(storage_path('app'));
        file_put_contents(self::pathFor($platform), $content);
    }
}
