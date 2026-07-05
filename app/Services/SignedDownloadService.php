<?php

namespace App\Services;

use App\Models\Download;
use App\Models\Setting;
use Illuminate\Support\Facades\URL;

class SignedDownloadService
{
    public function ttlHours(): int
    {
        return Setting::getInt('download_ttl_hours', 24);
    }

    public function signedUrl(Download $download): string
    {
        $expiresAt = $download->expires_at ?? now()->addHours($this->ttlHours());

        return URL::temporarySignedRoute(
            'downloads.file',
            $expiresAt,
            ['download' => $download]
        );
    }
}
