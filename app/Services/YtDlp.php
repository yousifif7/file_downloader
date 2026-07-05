<?php

namespace App\Services;

use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class YtDlp
{
    public static function binary(): string
    {
        $configured = config('downloader.yt_dlp_path');

        if (is_string($configured) && $configured !== '') {
            $normalized = self::normalizePath($configured);

            if (self::isUsableBinary($normalized)) {
                return $normalized;
            }
        }

        foreach (self::candidateBinaries() as $candidate) {
            if (File::exists($candidate)) {
                return $candidate;
            }
        }

        return 'yt-dlp';
    }

    /**
     * @return list<string>
     */
    private static function candidateBinaries(): array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return [
                base_path('bin/yt-dlp.exe'),
                base_path('bin/yt-dlp'),
            ];
        }

        return [
            base_path('bin/yt-dlp.sh'),
            base_path('bin/yt-dlp'),
        ];
    }

    private static function isUsableBinary(string $path): bool
    {
        if (! File::exists($path)) {
            return false;
        }

        if (PHP_OS_FAMILY !== 'Windows' && str_ends_with(strtolower($path), '.exe')) {
            return false;
        }

        return true;
    }

    private static function missingBinaryMessage(): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return 'yt-dlp was not found. Download yt-dlp.exe from https://github.com/yt-dlp/yt-dlp/releases and place it at: '.base_path('bin/yt-dlp.exe');
        }

        return 'yt-dlp was not found. Run: bash bin/setup-linux.sh (installs Python yt-dlp if the bundled binary is blocked).';
    }

    public static function ffmpegBinary(): ?string
    {
        $configured = config('downloader.ffmpeg_path');

        if (is_string($configured) && $configured !== '') {
            return self::resolveExecutablePath($configured);
        }

        $windowsBinary = base_path('bin/ffmpeg.exe');

        if (PHP_OS_FAMILY === 'Windows' && File::exists($windowsBinary)) {
            return $windowsBinary;
        }

        return self::resolveExecutablePath(base_path('bin/ffmpeg'));
    }

    public static function hasFfmpeg(): bool
    {
        return self::ffmpegBinary() !== null;
    }

    public static function tempDirectory(): string
    {
        $configured = config('downloader.yt_dlp_temp_path');

        if (is_string($configured) && $configured !== '') {
            $path = self::normalizePath($configured);
        } elseif (PHP_OS_FAMILY === 'Windows') {
            // Avoid paths with spaces (project folder often has them on Windows).
            $localAppData = getenv('LOCALAPPDATA');

            $path = is_string($localAppData) && $localAppData !== ''
                ? self::normalizePath($localAppData.DIRECTORY_SEPARATOR.'yt-dlp-temp')
                : self::normalizePath(storage_path('app/yt-dlp-temp'));
        } else {
            $path = self::normalizePath(storage_path('app/yt-dlp-temp'));
        }

        File::ensureDirectoryExists($path);

        return $path;
    }

    /**
     * @param  list<string>  $arguments
     * @return list<string>
     */
    public static function command(array $arguments, ?string $sourceUrl = null): array
    {
        $binary = self::binary();

        if ($binary !== 'yt-dlp' && ! File::exists($binary)) {
            throw new RuntimeException(self::missingBinaryMessage());
        }

        if (PHP_OS_FAMILY !== 'Windows' && $binary !== 'yt-dlp' && ! is_executable($binary)) {
            throw new RuntimeException('yt-dlp is not executable. Run: chmod +x '.$binary);
        }

        $tempDir = self::tempDirectory();

        $command = array_merge(
            [$binary],
            self::jsRuntimeArguments(),
            self::ejsComponentArguments(),
        );

        $cookies = is_string($sourceUrl) && $sourceUrl !== ''
            ? PlatformCookies::resolveForUrl($sourceUrl)
            : PlatformCookies::legacyFallbackPath();

        if (is_string($cookies) && $cookies !== '' && File::exists($cookies)) {
            $command[] = '--cookies';
            $command[] = $cookies;
        }

        $ffmpeg = self::ffmpegBinary();

        if (is_string($ffmpeg) && $ffmpeg !== '' && File::exists($ffmpeg)) {
            $command[] = '--ffmpeg-location';
            $command[] = $ffmpeg;
        }

        return array_merge($command, $arguments);
    }

    /**
     * @return list<string>
     */
    private static function jsRuntimeArguments(): array
    {
        $runtime = self::jsRuntime();

        if ($runtime === null) {
            return [];
        }

        return ['--js-runtimes', $runtime['type'].':'.$runtime['path']];
    }

    /**
     * @return array{type: string, path: string}|null
     */
    public static function jsRuntime(): ?array
    {
        $deno = self::resolveExecutablePath(
            config('downloader.yt_dlp_deno_path'),
            base_path('bin/deno'),
        );

        if ($deno !== null) {
            return ['type' => 'deno', 'path' => $deno];
        }

        $node = self::resolveExecutablePath(
            config('downloader.yt_dlp_node_path'),
            base_path('bin/node'),
            '/opt/alt/alt-nodejs22/root/usr/bin/node',
            '/opt/alt/alt-nodejs20/root/usr/bin/node',
        );

        if ($node !== null) {
            return ['type' => 'node', 'path' => $node];
        }

        return null;
    }

    public static function hasJsRuntime(): bool
    {
        return self::jsRuntime() !== null;
    }

    /**
     * @return list<string>
     */
    private static function ejsComponentArguments(): array
    {
        if (PHP_OS_FAMILY === 'Windows' && File::exists(base_path('bin/yt-dlp.exe'))) {
            return [];
        }

        if (File::exists(base_path('bin/yt-dlp.bin'))) {
            return [];
        }

        return ['--remote-components', 'ejs:github'];
    }

    private static function resolveExecutablePath(?string ...$candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (! is_string($candidate) || $candidate === '') {
                continue;
            }

            $path = self::normalizePath($candidate);

            if (! File::exists($path)) {
                continue;
            }

            if (PHP_OS_FAMILY !== 'Windows' && ! is_executable($path)) {
                continue;
            }

            return $path;
        }

        return null;
    }

    /**
     * @param  list<string>  $arguments
     */
    public static function run(array $arguments, int $timeout = 120, ?string $sourceUrl = null): ProcessResult
    {
        $tempDir = self::tempDirectory();

        return Process::timeout($timeout)
            ->env(self::processEnvironment($tempDir))
            ->run(self::command($arguments, $sourceUrl));
    }

    /**
     * @return array<string, string>
     */
    private static function processEnvironment(string $tempDir): array
    {
        $environment = [];

        foreach (array_merge($_ENV, $_SERVER) as $key => $value) {
            if (is_string($key) && is_string($value)) {
                $environment[$key] = $value;
            }
        }

        $fromGetenv = getenv();

        if (is_array($fromGetenv)) {
            foreach ($fromGetenv as $key => $value) {
                if (is_string($key) && is_string($value)) {
                    $environment[$key] = $value;
                }
            }
        }

        $environment['TEMP'] = $tempDir;
        $environment['TMP'] = $tempDir;
        $environment['TMPDIR'] = $tempDir;

        $python = config('downloader.yt_dlp_python');

        if (is_string($python) && $python !== '') {
            $environment['YT_DLP_PYTHON'] = $python;
        }

        return $environment;
    }

    private static function normalizePath(string $path): string
    {
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }
}
