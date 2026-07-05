<?php

namespace App\Services\Extractors;

use App\Services\YtDlp;
use RuntimeException;

class YoutubeExtractor extends YtDlpExtractor
{
    private const RELIABLE_FORMAT = '18/best[height<=480][ext=mp4]/best';

    protected function assertServerReady(): void
    {
        if (! YtDlp::hasJsRuntime()) {
            throw new RuntimeException(self::missingJsRuntimeMessage());
        }
    }

    public function supports(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host)) {
            return false;
        }

        $host = strtolower($host);

        return str_contains($host, 'youtube.com') || $host === 'youtu.be';
    }

    /**
     * @return list<string>
     */
    protected function ytDlpArguments(): array
    {
        $args = config('downloader.youtube_extractor_args', []);

        return is_array($args) ? $args : [];
    }

    protected function cleanUrl(string $url): string
    {
        $parts = parse_url($url);

        if (! is_array($parts)) {
            return $url;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($host !== 'youtu.be' && ! str_contains($host, 'youtube.com')) {
            return $url;
        }

        $path = $parts['path'] ?? '';

        if ($host === 'youtu.be' && is_string($path) && $path !== '') {
            return 'https://www.youtube.com/watch?v='.ltrim($path, '/');
        }

        if (! isset($parts['query']) || ! is_string($parts['query'])) {
            return $url;
        }

        parse_str($parts['query'], $query);

        if (! isset($query['v']) || ! is_string($query['v']) || $query['v'] === '') {
            return $url;
        }

        return 'https://www.youtube.com/watch?v='.$query['v'];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array{id: string, label: string, ext: string}>
     */
    protected function buildFormats(array $data): array
    {
        $maxHeight = 0;

        foreach ($data['formats'] ?? [] as $format) {
            if (! is_array($format)) {
                continue;
            }

            $formatId = (string) ($format['format_id'] ?? '');

            if ($formatId === '' || str_starts_with($formatId, 'sb')) {
                continue;
            }

            if (($format['vcodec'] ?? 'none') === 'none') {
                continue;
            }

            $maxHeight = max($maxHeight, (int) ($format['height'] ?? 0));
        }

        $hasFfmpeg = YtDlp::hasFfmpeg();

        $formats = [
            [
                'id' => self::RELIABLE_FORMAT,
                'label' => '360p MP4 (recommended)',
                'ext' => 'mp4',
            ],
        ];

        if ($hasFfmpeg) {
            $formats[] = [
                'id' => 'best[height<=480][ext=mp4]/best[height<=480]/best',
                'label' => '480p MP4',
                'ext' => 'mp4',
            ];

            if ($maxHeight === 0 || $maxHeight >= 720) {
                $formats[] = [
                    'id' => 'bestvideo[height<=720][ext=mp4]+bestaudio[ext=m4a]/best[height<=720][ext=mp4]/best',
                    'label' => '720p MP4',
                    'ext' => 'mp4',
                ];
            }

            if ($maxHeight >= 1080) {
                $formats[] = [
                    'id' => 'bestvideo[height<=1080][ext=mp4]+bestaudio[ext=m4a]/best[height<=1080]/best',
                    'label' => '1080p MP4',
                    'ext' => 'mp4',
                ];
            }
        }

        $formats[] = [
            'id' => 'audio-extract',
            'label' => 'Audio only (M4A)',
            'ext' => 'm4a',
        ];

        return array_slice($formats, 0, 6);
    }

    public function download(string $url, string $formatId): array
    {
        $playerClients = [
            [],
            ['--extractor-args', 'youtube:player_client=android'],
            ['--extractor-args', 'youtube:player_client=tv_embedded'],
            ['--extractor-args', 'youtube:player_client=ios'],
        ];

        if ($this->isAudioOnlyFormat($formatId)) {
            return $this->downloadAudio($url, $playerClients);
        }

        $formatCandidates = array_values(array_unique([
            $formatId,
            self::RELIABLE_FORMAT.'|mp4',
            'best[ext=mp4]/best|mp4',
        ]));

        return $this->downloadWithRetries($url, $formatCandidates, $playerClients, false);
    }

    /**
     * @param  list<list<string>>  $playerClients
     * @return array{path: string, size: int}
     */
    private function downloadAudio(string $url, array $playerClients): array
    {
        if (! YtDlp::hasFfmpeg()) {
            throw new RuntimeException(
                'Audio only needs FFmpeg on this server. Run: bash bin/setup-linux.sh then chmod +x bin/ffmpeg'
            );
        }

        $lastException = null;
        $sourceFormats = ['18', 'best[height<=480][ext=mp4]/best'];

        foreach ($playerClients as $clientArgs) {
            foreach ($sourceFormats as $sourceFormat) {
                try {
                    return $this->executeExtractAudioDownload($url, $sourceFormat, 'm4a', $clientArgs);
                } catch (RuntimeException $exception) {
                    if ($this->isNonRetryableError($exception->getMessage())) {
                        throw $exception;
                    }

                    $lastException = $exception;
                }
            }
        }

        $directAudioFormats = [
            'bestaudio[ext=m4a]/bestaudio|m4a',
            'bestaudio/best|m4a',
        ];

        try {
            return $this->downloadWithRetries($url, $directAudioFormats, $playerClients, true);
        } catch (RuntimeException $exception) {
            $lastException = $exception;
        }

        throw new RuntimeException(
            isset($lastException)
                ? $lastException->getMessage()
                : 'YouTube blocked the audio download. Try 360p MP4 instead, or refresh cookies in Admin → Settings.'
        );
    }

    /**
     * @param  list<string>  $formatCandidates
     * @param  list<list<string>>  $playerClients
     * @return array{path: string, size: int}
     */
    private function downloadWithRetries(string $url, array $formatCandidates, array $playerClients, bool $audioOnly): array
    {
        $lastException = null;

        foreach ($playerClients as $clientArgs) {
            foreach ($formatCandidates as $candidateFormat) {
                try {
                    return $this->executeDownload($url, $candidateFormat, $clientArgs);
                } catch (RuntimeException $exception) {
                    $lastException = $exception;

                    if ($this->isNonRetryableError($exception->getMessage())) {
                        throw $exception;
                    }

                    if (! $this->isRetryableDownloadError($exception->getMessage())) {
                        throw $exception;
                    }
                }
            }
        }

        if ($lastException !== null) {
            throw new RuntimeException(
                $audioOnly
                    ? 'YouTube blocked the audio download. Refresh cookies in Admin → Settings, or try again later.'
                    : 'YouTube blocked the video download (HTTP 403). Try "360p MP4 (recommended)", refresh cookies in Admin → Settings, or download again later.'
            );
        }

        throw new RuntimeException('YouTube download failed.');
    }

    private function isAudioOnlyFormat(string $formatSelection): bool
    {
        [$formatId, $extension] = $this->parseFormatSelection($formatSelection);

        if ($formatId === 'audio-extract') {
            return true;
        }

        if (in_array($extension, ['m4a', 'mp3', 'opus', 'webm', 'aac'], true)) {
            return true;
        }

        return str_contains($formatId, 'bestaudio') && ! str_contains($formatId, 'bestvideo');
    }

    private function isNonRetryableError(string $message): bool
    {
        $lower = strtolower($message);

        return str_contains($lower, 'sign in to confirm')
            || str_contains($lower, 'not a bot')
            || str_contains($lower, 'cookies are no longer valid')
            || str_contains($lower, 'have likely been rotated')
            || str_contains($lower, 'youtube blocked');
    }

    private function isRetryableDownloadError(string $message): bool
    {
        $needles = [
            'HTTP Error 403',
            '403: Forbidden',
            'Requested format is not available',
            'unable to download video data',
            'No video formats found',
        ];

        foreach ($needles as $needle) {
            if (str_contains($message, $needle)) {
                return true;
            }
        }

        return false;
    }
}
