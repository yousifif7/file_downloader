<?php

namespace App\Services;

class DownloadFailureClassifier
{
    /**
     * @return array<string, string>
     */
    public function options(): array
    {
        return [
            'cookies' => 'Cookies',
            'ffmpeg' => 'FFmpeg',
            'deno' => 'Deno',
            'yt-dlp' => 'yt-dlp',
            'quota' => 'Quota',
            'unknown' => 'Unknown',
        ];
    }

    public function classify(?string $message): string
    {
        $normalized = strtolower(trim((string) $message));

        if ($normalized === '') {
            return 'unknown';
        }

        foreach ($this->patterns() as $key => $patterns) {
            foreach ($patterns as $pattern) {
                if (str_contains($normalized, $pattern)) {
                    return $key;
                }
            }
        }

        return 'unknown';
    }

    public function label(?string $message): string
    {
        return $this->options()[$this->classify($message)] ?? 'Unknown';
    }

    /**
     * @return list<string>
     */
    public function patternsFor(string $type): array
    {
        return $this->patterns()[$type] ?? [];
    }

    /**
     * @return array<string, list<string>>
     */
    private function patterns(): array
    {
        return [
            'cookies' => ['cookie', 'cookies', 'sign in to confirm', 'age-restricted', 'captcha'],
            'ffmpeg' => ['ffmpeg', 'merge', 'postprocess'],
            'deno' => ['deno', 'js runtime', 'javascript runtime'],
            'yt-dlp' => ['yt-dlp', 'extractor', 'unsupported url', 'requested format is not available'],
            'quota' => ['quota', 'limit', 'too many download requests'],
        ];
    }
}
