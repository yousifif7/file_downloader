<?php

namespace App\Services;

class UrlDetector
{
    private const HOST_MAP = [
        'youtube.com' => 'youtube',
        'www.youtube.com' => 'youtube',
        'm.youtube.com' => 'youtube',
        'youtu.be' => 'youtube',
        'music.youtube.com' => 'youtube',
        'tiktok.com' => 'tiktok',
        'www.tiktok.com' => 'tiktok',
        'vm.tiktok.com' => 'tiktok',
        'vt.tiktok.com' => 'tiktok',
        'twitter.com' => 'twitter',
        'www.twitter.com' => 'twitter',
        'mobile.twitter.com' => 'twitter',
        'x.com' => 'twitter',
        'www.x.com' => 'twitter',
        'instagram.com' => 'instagram',
        'www.instagram.com' => 'instagram',
        'm.instagram.com' => 'instagram',
        'facebook.com' => 'facebook',
        'www.facebook.com' => 'facebook',
        'm.facebook.com' => 'facebook',
        'fb.watch' => 'facebook',
        'linkedin.com' => 'linkedin',
        'www.linkedin.com' => 'linkedin',
        'lnkd.in' => 'linkedin',
    ];

    private const DIRECT_EXTENSIONS = [
        'mp4', 'webm', 'mkv', 'avi', 'mov', 'mp3', 'm4a', 'wav', 'flac',
        'pdf', 'zip', 'rar', '7z', 'tar', 'gz',
        'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg',
        'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
    ];

    public function detect(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return null;
        }

        $host = strtolower($host);

        if (isset(self::HOST_MAP[$host])) {
            return self::HOST_MAP[$host];
        }

        if ($this->isDirectFileUrl($url)) {
            return 'direct';
        }

        return null;
    }

    private function isDirectFileUrl(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return false;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, self::DIRECT_EXTENSIONS, true);
    }
}
