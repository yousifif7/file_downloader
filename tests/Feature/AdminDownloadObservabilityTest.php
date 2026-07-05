<?php

namespace Tests\Feature;

use App\Jobs\ProcessDownloadJob;
use App\Models\Download;
use App\Models\Platform;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AdminDownloadObservabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_downloads_index_can_filter_by_platform_and_failure_type(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $youtube = Platform::query()->create([
            'name' => 'YouTube',
            'slug' => 'youtube',
            'extractor_class' => 'App\\Services\\Extractors\\YoutubeExtractor',
            'is_enabled' => true,
        ]);
        $tiktok = Platform::query()->create([
            'name' => 'TikTok',
            'slug' => 'tiktok',
            'extractor_class' => 'App\\Services\\Extractors\\YtDlpExtractor',
            'is_enabled' => true,
        ]);

        Download::query()->create([
            'user_id' => $admin->id,
            'platform_id' => $youtube->id,
            'source_url' => 'https://youtube.com/watch?v=1',
            'status' => Download::STATUS_FAILED,
            'error_message' => 'Cookie authentication required before download.',
        ]);

        Download::query()->create([
            'user_id' => $admin->id,
            'platform_id' => $tiktok->id,
            'source_url' => 'https://tiktok.com/@demo/video/2',
            'status' => Download::STATUS_FAILED,
            'error_message' => 'FFmpeg merge failed while postprocess step was running.',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.downloads.index', [
            'platform_id' => $youtube->id,
            'failure_type' => 'cookies',
        ]));

        $response->assertOk();
        $response->assertSee('YouTube');
        $response->assertSee('Cookies');
        $response->assertSee('https://youtube.com/watch?v=1');
        $response->assertDontSee('https://tiktok.com/@demo/video/2');
        $response->assertDontSee('FFmpeg merge failed while postprocess step was running.');
    }

    public function test_admin_can_retry_a_failed_download(): void
    {
        Queue::fake();

        $admin = User::factory()->create(['is_admin' => true]);
        $download = Download::query()->create([
            'user_id' => $admin->id,
            'source_url' => 'https://example.com/file.mp4',
            'status' => Download::STATUS_FAILED,
            'error_message' => 'Temporary extractor failure.',
            'file_path' => 'downloads/test.mp4',
            'file_size_bytes' => 12345,
            'completed_at' => now(),
            'expires_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.downloads.retry', $download));

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Download queued for retry.');

        $download->refresh();

        $this->assertSame(Download::STATUS_PENDING, $download->status);
        $this->assertNull($download->error_message);
        $this->assertNull($download->file_path);
        $this->assertNull($download->completed_at);
        $this->assertNull($download->expires_at);

        Queue::assertPushed(ProcessDownloadJob::class);
    }
}
