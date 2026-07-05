<?php

namespace App\Console\Commands;

use App\Models\Download;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupExpiredDownloads extends Command
{
    protected $signature = 'downloads:cleanup';

    protected $description = 'Delete expired download files and mark records as expired';

    public function handle(): int
    {
        $downloads = Download::query()
            ->whereIn('status', [Download::STATUS_COMPLETED, Download::STATUS_FAILED])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        foreach ($downloads as $download) {
            if ($download->file_path) {
                Storage::disk('local')->delete($download->file_path);
            }

            $download->update([
                'status' => Download::STATUS_EXPIRED,
                'file_path' => null,
            ]);
        }

        $this->info("Cleaned up {$downloads->count()} expired downloads.");

        return self::SUCCESS;
    }
}
