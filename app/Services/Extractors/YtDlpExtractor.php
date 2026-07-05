<?php

namespace App\Services\Extractors;

use App\Services\YtDlp;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class YtDlpExtractor implements ExtractorInterface
{
    private const VIDEO_EXTENSIONS = ['mp4', 'webm', 'mkv', 'mov'];

    public function supports(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    public function metadata(string $url): array
    {
        $data = $this->runJsonDump($this->cleanUrl($url));

        return [
            'title' => (string) ($data['title'] ?? 'Download'),
            'thumbnail' => $data['thumbnail'] ?? null,
            'formats' => $this->buildFormats($data),
        ];
    }

    public function download(string $url, string $formatId): array
    {
        return $this->executeDownload($url, $formatId);
    }

    /**
     * @param  list<string>  $additionalArguments
     * @return array{path: string, size: int}
     */
    protected function executeDownload(string $url, string $formatId, array $additionalArguments = []): array
    {
        $this->assertServerReady();

        Storage::disk('local')->makeDirectory('downloads');

        [$resolvedFormatId, $preferredExtension] = $this->parseFormatSelection($formatId);
        $preferredExtension = $this->normalizeExpectedExtension($preferredExtension, $resolvedFormatId);

        $uuid = (string) Str::uuid();
        $outputTemplate = Storage::disk('local')->path("downloads/{$uuid}.%(ext)s");

        $command = array_merge(
            $this->allYtDlpArguments(),
            $additionalArguments,
            [
                '-f', $resolvedFormatId,
                '--no-playlist',
                '-o', $outputTemplate,
            ]
        );

        if ($this->shouldRemuxVideo($resolvedFormatId, $preferredExtension)) {
            $command[] = '--merge-output-format';
            $command[] = $preferredExtension;
            $command[] = '--remux-video';
            $command[] = $preferredExtension;
        }

        $command[] = $this->cleanUrl($url);

        $result = YtDlp::run($command, 600, $url);

        if (! $result->successful()) {
            throw new RuntimeException($this->resolveYtDlpError($result) ?: 'yt-dlp failed to download this file.');
        }

        $matches = glob(Storage::disk('local')->path("downloads/{$uuid}.*"));

        if ($matches === false || $matches === []) {
            throw new RuntimeException('Download finished but no file was created.');
        }

        $absolutePath = $matches[0];
        $absolutePath = $this->finalizeDownloadedFile($absolutePath, $preferredExtension, $resolvedFormatId);

        $relativePath = 'downloads/'.basename($absolutePath);
        $size = filesize($absolutePath) ?: 0;

        return [
            'path' => $relativePath,
            'size' => $size,
        ];
    }

    /**
     * Download a progressive video stream and extract audio (same path as reliable 360p video).
     *
     * @param  list<string>  $additionalArguments
     * @return array{path: string, size: int}
     */
    protected function executeExtractAudioDownload(
        string $url,
        string $sourceFormat = '18',
        string $audioExtension = 'm4a',
        array $additionalArguments = [],
    ): array {
        $this->assertServerReady();

        if (! YtDlp::hasFfmpeg()) {
            throw new RuntimeException('Audio extraction requires FFmpeg. Run: bash bin/setup-linux.sh');
        }

        Storage::disk('local')->makeDirectory('downloads');

        $uuid = (string) Str::uuid();
        $outputTemplate = Storage::disk('local')->path("downloads/{$uuid}.%(ext)s");

        $command = array_merge(
            $this->allYtDlpArguments(),
            $additionalArguments,
            [
                '-f', $sourceFormat,
                '--extract-audio',
                '--audio-format', $audioExtension,
                '--no-playlist',
                '-o', $outputTemplate,
                $this->cleanUrl($url),
            ]
        );

        $result = YtDlp::run($command, 600, $url);

        if (! $result->successful()) {
            throw new RuntimeException($this->resolveYtDlpError($result) ?: 'yt-dlp failed to extract audio.');
        }

        $matches = glob(Storage::disk('local')->path("downloads/{$uuid}.*"));

        if ($matches === false || $matches === []) {
            throw new RuntimeException('Audio extraction finished but no file was created.');
        }

        $absolutePath = $matches[0];

        foreach ($matches as $match) {
            if (preg_match('/\.(m4a|mp3|opus|aac|webm)$/i', $match)) {
                $absolutePath = $match;
                break;
            }
        }

        $absolutePath = $this->finalizeDownloadedFile($absolutePath, $audioExtension, 'bestaudio');

        $relativePath = 'downloads/'.basename($absolutePath);
        $size = filesize($absolutePath) ?: 0;

        return [
            'path' => $relativePath,
            'size' => $size,
        ];
    }

    protected function shouldRemuxVideo(string $formatId, ?string $preferredExtension): bool
    {
        if ($preferredExtension === null || ! in_array($preferredExtension, self::VIDEO_EXTENSIONS, true)) {
            return false;
        }

        if (str_contains($formatId, 'bestaudio') && ! str_contains($formatId, 'bestvideo')) {
            return false;
        }

        if (str_contains($formatId, '+') || str_contains($formatId, 'bestvideo')) {
            return true;
        }

        return ! preg_match('/\b18\b/', $formatId);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array{id: string, label: string, ext: string}>
     */
    protected function buildFormats(array $data): array
    {
        $candidates = [];

        foreach ($data['formats'] ?? [] as $format) {
            if (! isset($format['format_id'], $format['ext'])) {
                continue;
            }

            $formatId = (string) $format['format_id'];
            $vcodec = (string) ($format['vcodec'] ?? 'none');
            $acodec = (string) ($format['acodec'] ?? 'none');

            if ($formatId === 'download' || str_starts_with($formatId, 'sb')) {
                continue;
            }

            if ($vcodec === 'none' && $acodec === 'none') {
                continue;
            }

            if (preg_match('/-\d+$/', $formatId) && str_ends_with($formatId, '-1')) {
                continue;
            }

            $extension = $this->normalizeExpectedExtension(strtolower((string) $format['ext']));
            $height = $this->resolveHeight($formatId, (int) ($format['height'] ?? 0));
            $tbr = (int) ($format['tbr'] ?? 0);

            if ($vcodec === 'none') {
                $key = 'audio-'.$extension;
                $label = 'Audio '.strtoupper($extension);
            } else {
                $codec = str_contains($vcodec, '265') || str_contains($vcodec, 'hevc') ? 'H.265' : 'H.264';
                $label = $height > 0
                    ? "{$height}p {$codec}"
                    : strtoupper($extension).' '.$codec;
                $key = $height.'-'.$extension.'-'.$codec;
            }

            if (! isset($candidates[$key]) || $tbr >= $candidates[$key]['tbr']) {
                $candidates[$key] = [
                    'id' => $formatId,
                    'label' => $label,
                    'ext' => $extension,
                    'height' => $height,
                    'tbr' => $tbr,
                ];
            }
        }

        $formats = collect($candidates)
            ->sortByDesc(fn (array $format): array => [$format['height'], $format['tbr']])
            ->values();

        $hasDownload = collect($data['formats'] ?? [])
            ->contains(fn (array $format): bool => ($format['format_id'] ?? '') === 'download');

        if ($hasDownload) {
            $formats->prepend([
                'id' => 'download',
                'label' => 'Best quality (recommended)',
                'ext' => 'mp4',
                'height' => PHP_INT_MAX,
                'tbr' => PHP_INT_MAX,
            ]);
        }

        $formats = $formats
            ->unique('label')
            ->take(6)
            ->map(fn (array $format): array => [
                'id' => $format['id'],
                'label' => $format['label'],
                'ext' => $format['ext'],
            ])
            ->values()
            ->all();

        if ($formats === []) {
            $formats[] = [
                'id' => 'best[ext=mp4]/best',
                'label' => 'Best available',
                'ext' => 'mp4',
            ];
        }

        return $formats;
    }

    /**
     * @return list<string>
     */
    protected function ytDlpArguments(): array
    {
        return [];
    }

    /**
     * @return list<string>
     */
    protected function allYtDlpArguments(): array
    {
        return array_merge(
            ['--ignore-no-formats-error'],
            $this->ytDlpArguments(),
        );
    }

    protected function assertServerReady(): void
    {
    }

    public static function missingJsRuntimeMessage(): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return 'YouTube requires Deno on the server. This is only needed on Linux production hosting.';
        }

        return 'YouTube needs Deno on this server. SSH or hPanel Terminal: cd '
            .base_path().' && bash bin/setup-linux.sh  (or: php bin/install-deno.php) then php artisan config:clear';
    }

    /**
     * @return array<string, mixed>
     */
    private function runJsonDump(string $url): array
    {
        $this->assertServerReady();

        $result = YtDlp::run(array_merge(
            $this->allYtDlpArguments(),
            [
                '--dump-json',
                '--no-playlist',
                '--no-warnings',
                $url,
            ]
        ), 120, $url);

        if (! $result->successful()) {
            $error = $this->resolveYtDlpError($result);
            $platformLabel = $this->platformLabelForUrl($url);

            if (str_contains($error, 'not recognized') || str_contains($error, 'No such file')) {
                throw new RuntimeException(
                    'yt-dlp is not installed. Set YT_DLP_PATH in your .env file.'
                );
            }

            if ($this->isAuthRestrictedError($error)) {
                throw new RuntimeException(
                    $platformLabel.' requires login, fresh cookies, or a public post. Try again later or upload fresh '.$platformLabel.' cookies in Admin -> Settings.'
                );
            }

            if ($this->isExpiredCookiesError($error)) {
                throw new RuntimeException(
                    $platformLabel.' cookies expired. Export a fresh cookies.txt from a logged-in browser and upload it in Admin -> Settings.'
                );
            }

            if ($this->isSignatureRuntimeError($error)) {
                throw new RuntimeException(
                    YtDlp::hasJsRuntime()
                        ? $platformLabel.' blocked this request or returned no formats. Refresh platform cookies if needed, then php artisan config:clear'
                        : self::missingJsRuntimeMessage()
                );
            }

            throw new RuntimeException($error !== '' ? $error : 'yt-dlp could not read this URL.');
        }

        $data = json_decode($result->output(), true);

        if (! is_array($data)) {
            throw new RuntimeException('Could not parse metadata from yt-dlp.');
        }

        return $data;
    }

    protected function cleanUrl(string $url): string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $parts = parse_url($url);

        if (! is_array($parts)) {
            return $url;
        }

        if (in_array($host, ['tiktok.com', 'www.tiktok.com', 'vm.tiktok.com', 'vt.tiktok.com'], true)) {
            return ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '').($parts['path'] ?? '');
        }

        return $this->rebuildUrl($parts, ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content']);
    }

    private function resolveYtDlpError(\Illuminate\Contracts\Process\ProcessResult $result): string
    {
        $stderr = trim($result->errorOutput());
        $stdout = trim($result->output());
        $combined = $stderr !== '' ? $stderr : $stdout;

        foreach (preg_split('/\r\n|\r|\n/', $combined) ?: [] as $line) {
            if (str_starts_with($line, 'ERROR:')) {
                $combined = $line;

                break;
            }
        }

        if (str_contains($combined, 'HTTP Error 403') || str_contains($combined, 'unable to download video data')) {
            return $combined;
        }

        if (str_contains($combined, 'Signature solving failed') || str_contains($combined, 'No video formats found')) {
            return YtDlp::hasJsRuntime()
                ? 'This platform blocked the request or cookies expired. Refresh the platform cookies in Admin -> Settings, then php artisan config:clear'
                : self::missingJsRuntimeMessage();
        }

        if ($combined !== '' && ! str_starts_with($combined, '{')) {
            return $combined;
        }

        return 'yt-dlp failed (exit '.$result->exitCode().'). Check storage/logs/laravel.log.';
    }

    /**
     * @return array{0: string, 1: ?string}
     */
    protected function parseFormatSelection(string $formatSelection): array
    {
        $parts = explode('|', $formatSelection, 2);
        $formatId = $parts[0] !== '' ? $parts[0] : 'best[ext=mp4]/best';
        $extension = $parts[1] ?? null;

        return [$formatId, $extension !== null ? strtolower($extension) : null];
    }

    private function normalizeExpectedExtension(?string $extension, ?string $formatId = null): ?string
    {
        if ($extension === null || $extension === '' || $extension === 'bin') {
            if ($formatId !== null && str_contains($formatId, 'bestaudio') && ! str_contains($formatId, 'bestvideo')) {
                return 'm4a';
            }

            return 'mp4';
        }

        return $extension;
    }

    private function finalizeDownloadedFile(string $absolutePath, ?string $preferredExtension, ?string $formatId = null): string
    {
        $detectedExtension = $this->detectMediaExtension($absolutePath);

        if ($detectedExtension === 'html') {
            @unlink($absolutePath);
            throw new RuntimeException(
                'Download failed: the source returned a web page instead of media. Try Best quality (recommended), confirm the post is public, or try again later.'
            );
        }

        $targetExtension = $this->resolveTargetExtension($detectedExtension, $preferredExtension, $formatId);

        if ($targetExtension === null) {
            return $absolutePath;
        }

        $currentExtension = strtolower((string) pathinfo($absolutePath, PATHINFO_EXTENSION));

        if ($currentExtension === $targetExtension) {
            return $absolutePath;
        }

        if ($detectedExtension === null && in_array($currentExtension, ['bin', 'html', 'txt'], true)) {
            @unlink($absolutePath);
            throw new RuntimeException('Download failed: the file was not a valid video. Try Best quality (recommended).');
        }

        $normalizedPath = preg_replace('/\.[^.]+$/', '', $absolutePath).'.'.$targetExtension;

        if (! is_string($normalizedPath) || $normalizedPath === $absolutePath) {
            return $absolutePath;
        }

        @rename($absolutePath, $normalizedPath);

        return is_file($normalizedPath) ? $normalizedPath : $absolutePath;
    }

    private function resolveTargetExtension(?string $detectedExtension, ?string $preferredExtension, ?string $formatId): ?string
    {
        $audioExtensions = ['m4a', 'mp3', 'opus', 'aac', 'webm'];
        $isAudioOnly = $formatId !== null
            && str_contains($formatId, 'bestaudio')
            && ! str_contains($formatId, 'bestvideo');

        if ($isAudioOnly && in_array($preferredExtension, $audioExtensions, true)) {
            return $preferredExtension;
        }

        if (
            in_array($preferredExtension, ['m4a', 'mp3', 'opus', 'aac'], true)
            && $detectedExtension === 'mp4'
        ) {
            return $preferredExtension;
        }

        return $detectedExtension ?? $preferredExtension;
    }

    private function resolveHeight(string $formatId, int $reportedHeight): int
    {
        if (preg_match('/(\d{3,4})p/i', $formatId, $matches)) {
            return (int) $matches[1];
        }

        return $reportedHeight;
    }

    private function detectMediaExtension(string $absolutePath): ?string
    {
        $handle = fopen($absolutePath, 'rb');

        if ($handle === false) {
            return null;
        }

        $header = fread($handle, 16);
        fclose($handle);

        if (! is_string($header) || $header === '') {
            return null;
        }

        $lower = strtolower($header);

        if (str_starts_with($lower, '<!doctype') || str_starts_with($lower, '<html') || str_starts_with($lower, '<head')) {
            return 'html';
        }

        if (strlen($header) >= 8 && substr($header, 4, 4) === 'ftyp') {
            return 'mp4';
        }

        if (str_starts_with($header, 'ID3') || str_starts_with($header, "\xFF\xFB")) {
            return 'mp3';
        }

        if (str_starts_with($header, 'RIFF')) {
            return 'webm';
        }

        return null;
    }

    private function isAuthRestrictedError(string $error): bool
    {
        $normalized = strtolower($error);

        return str_contains($normalized, 'sign in to confirm')
            || str_contains($normalized, 'not a bot')
            || str_contains($normalized, 'login required')
            || str_contains($normalized, 'requested content is not available')
            || str_contains($normalized, 'private')
            || str_contains($normalized, 'forbidden');
    }

    private function isExpiredCookiesError(string $error): bool
    {
        $normalized = strtolower($error);

        return str_contains($normalized, 'cookies are no longer valid')
            || str_contains($normalized, 'have likely been rotated')
            || str_contains($normalized, 'login information');
    }

    private function isSignatureRuntimeError(string $error): bool
    {
        return str_contains($error, 'Signature solving failed')
            || str_contains($error, 'No video formats found');
    }

    private function platformLabelForUrl(string $url): string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return match ($host) {
            'instagram.com', 'www.instagram.com', 'm.instagram.com' => 'Instagram',
            'facebook.com', 'www.facebook.com', 'm.facebook.com', 'fb.watch' => 'Facebook',
            'linkedin.com', 'www.linkedin.com', 'lnkd.in' => 'LinkedIn',
            'twitter.com', 'www.twitter.com', 'mobile.twitter.com', 'x.com', 'www.x.com' => 'Twitter / X',
            'tiktok.com', 'www.tiktok.com', 'vm.tiktok.com', 'vt.tiktok.com' => 'TikTok',
            default => 'This platform',
        };
    }

    /**
     * @param  array<string, mixed>  $parts
     * @param  list<string>  $ignoredQueryKeys
     */
    private function rebuildUrl(array $parts, array $ignoredQueryKeys = []): string
    {
        $scheme = is_string($parts['scheme'] ?? null) ? $parts['scheme'] : 'https';
        $host = is_string($parts['host'] ?? null) ? $parts['host'] : '';
        $path = is_string($parts['path'] ?? null) ? $parts['path'] : '';

        if ($host === '') {
            return '';
        }

        $query = '';

        if (isset($parts['query']) && is_string($parts['query']) && $parts['query'] !== '') {
            parse_str($parts['query'], $queryValues);

            foreach ($ignoredQueryKeys as $key) {
                unset($queryValues[$key]);
            }

            $query = http_build_query($queryValues);
        }

        return $scheme.'://'.$host.$path.($query !== '' ? '?'.$query : '');
    }
}
