<?php

namespace App\Console\Commands;

use App\Services\PlatformCookies;
use App\Services\YtDlp;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class DownloaderDoctor extends Command
{
    protected $signature = 'downloader:doctor';

    protected $description = 'Check yt-dlp, Python, Deno, FFmpeg, and cookies on this server';

    public function handle(): int
    {
        $this->info('Downloader server check');
        $this->newLine();

        $issues = 0;

        if (file_exists(base_path('bootstrap/cache/config.php'))) {
            $this->warn('Config is cached — after .env changes run: php artisan config:clear');
            $this->newLine();
        }

        $issues += $this->checkBinary('yt-dlp', YtDlp::binary(), ['--version']);
        $issues += $this->checkPython();
        $issues += $this->checkJsRuntime();
        $issues += $this->checkFfmpeg();
        $issues += $this->checkCookies();

        $this->newLine();

        if ($issues > 0) {
            $this->error("{$issues} issue(s) found.");
            $this->line('Fix on Hostinger (SSH or hPanel Terminal):');
            $this->line('  cd '.base_path());
            $this->line('  bash bin/setup-linux.sh');
            $this->line('  # or if bash fails:');
            $this->line('  php bin/install-deno.php');
            $this->line('  chmod +x bin/deno bin/yt-dlp.sh');
            $this->line('  php artisan config:clear');

            return self::FAILURE;
        }

        $this->info('All checks passed. Supported platform downloads should work (cookies may still be needed).');

        return self::SUCCESS;
    }

    private function checkBinary(string $label, string $path, array $versionArgs): int
    {
        $this->line("<fg=cyan>{$label}</>");

        if ($path === 'yt-dlp') {
            $this->line('  Path: system PATH (not bundled)');
        } else {
            $this->line("  Path: {$path}");
        }

        if ($path !== 'yt-dlp' && ! File::exists($path)) {
            $this->error('  Missing file');

            return 1;
        }

        if ($path !== 'yt-dlp' && PHP_OS_FAMILY !== 'Windows' && ! is_executable($path)) {
            $this->error('  Not executable — run: chmod +x '.$path);

            return 1;
        }

        $result = Process::timeout(30)->run(array_merge([$path], $versionArgs));

        if ($result->successful()) {
            $version = trim(strtok($result->output(), "\n"));
            $this->info('  OK — '.$version);

            return 0;
        }

        $this->error('  Failed — '.trim($result->errorOutput() ?: $result->output() ?: 'unknown error'));

        return 1;
    }

    private function checkPython(): int
    {
        $python = config('downloader.yt_dlp_python');

        $this->line('<fg=cyan>Python (yt-dlp)</>');

        if (! is_string($python) || $python === '') {
            $this->warn('  YT_DLP_PYTHON not set — yt-dlp.sh will auto-detect');
            $this->warn('  On Hostinger you usually need: YT_DLP_PYTHON=/opt/alt/python311/bin/python3.11');

            return 0;
        }

        $this->line("  Path: {$python}");

        if (! File::exists($python)) {
            $this->error('  Missing — fix YT_DLP_PYTHON in .env');

            return 1;
        }

        $result = Process::timeout(15)->run([$python, '--version']);

        if ($result->successful()) {
            $this->info('  OK — '.trim($result->output()));

            return 0;
        }

        $this->error('  Cannot run Python at this path');

        return 1;
    }

    private function checkJsRuntime(): int
    {
        $this->line('<fg=cyan>JS runtime (YouTube)</>');

        $runtime = YtDlp::jsRuntime();

        if ($runtime === null) {
            $this->error('  Missing — YouTube needs Deno or Node 22+');
            $this->line('  Expected: '.base_path('bin/deno'));
            $this->line('  Install: bash bin/setup-linux.sh  OR  php bin/install-deno.php');

            return 1;
        }

        $this->line("  {$runtime['type']}: {$runtime['path']}");

        if (! is_executable($runtime['path']) && PHP_OS_FAMILY !== 'Windows') {
            $this->error('  Not executable — run: chmod +x '.$runtime['path']);

            return 1;
        }

        $result = Process::timeout(20)->run([$runtime['path'], '--version']);

        if ($result->successful()) {
            $this->info('  OK — '.trim(strtok($result->output(), "\n")));

            return 0;
        }

        $this->error('  Failed to run — '.trim($result->errorOutput() ?: $result->output()));

        return 1;
    }

    private function checkFfmpeg(): int
    {
        $this->line('<fg=cyan>FFmpeg</> (required for 480p+ YouTube — merges video + audio)');

        $path = YtDlp::ffmpegBinary();

        if ($path === null) {
            $expected = base_path('bin/ffmpeg');
            $archive = base_path('bin/ffmpeg-linux.tar.xz');

            $this->error('  Missing — only 360p video will be offered without FFmpeg');
            $this->line("  Expected: {$expected}");

            if (file_exists($archive)) {
                $this->line('  Archive found — run: bash bin/setup-linux.sh');
            } else {
                $this->line('  Upload bin/ffmpeg-linux.tar.xz or bin/ffmpeg, then run: bash bin/setup-linux.sh');
            }

            return 1;
        }

        $this->line("  Path: {$path}");

        if (PHP_OS_FAMILY !== 'Windows' && ! is_executable($path)) {
            $this->error('  Not executable — run: chmod +x '.$path);

            return 1;
        }

        $result = Process::timeout(20)->run([$path, '-version']);

        if ($result->successful()) {
            $this->info('  OK — '.trim(strtok($result->output(), "\n")));

            return 0;
        }

        $this->error('  Failed — '.trim($result->errorOutput() ?: $result->output()));

        return 1;
    }

    private function checkOptionalBinary(string $label, ?string $path, array $versionArgs): int
    {
        $this->line("<fg=cyan>{$label}</>");

        if ($path === null || $path === '') {
            $this->warn('  Not found (optional for some formats)');

            return 0;
        }

        return $this->checkBinary($label, $path, $versionArgs);
    }

    private function checkCookies(): int
    {
        $this->line('<fg=cyan>Platform cookies</>');

        foreach (PlatformCookies::status() as $platform => $cookieStatus) {
            $label = $cookieStatus['name'];

            if (! $cookieStatus['exists']) {
                $this->warn("  {$label}: not set");

                continue;
            }

            $this->line("  {$label}: {$cookieStatus['path']}");
            $this->info('    OK — file present');
        }

        return 0;
    }
}
