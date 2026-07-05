<?php

namespace App\Jobs;

use App\Models\Download;
use App\Models\Setting;
use App\Services\DownloadQuotaService;
use App\Services\ExtractorRegistry;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessDownloadJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 900;

    public function __construct(
        public Download $download,
    ) {}

    public function handle(ExtractorRegistry $registry, DownloadQuotaService $quotaService): void
    {
        $this->download->refresh();

        if ($this->download->status !== Download::STATUS_PENDING) {
            return;
        }

        $this->download->update(['status' => Download::STATUS_PROCESSING]);

        try {
            $extractor = $registry->resolveForUrl($this->download->source_url);
            $result = $extractor->download($this->download->source_url, (string) $this->download->format);

            $ttlHours = Setting::getInt('download_ttl_hours', 24);

            $this->download->update([
                'status' => Download::STATUS_COMPLETED,
                'file_path' => $result['path'],
                'file_size_bytes' => $result['size'],
                'completed_at' => now(),
                'expires_at' => now()->addHours($ttlHours),
                'error_message' => null,
            ]);

            if ($this->download->user !== null) {
                $quotaService->incrementUsage($this->download->user);
            }
        } catch (Throwable $exception) {
            if ($this->download->file_path) {
                Storage::disk('local')->delete($this->download->file_path);
            }

            $this->download->update([
                'status' => Download::STATUS_FAILED,
                'error_message' => $exception->getMessage(),
            ]);
        }
    }
}
