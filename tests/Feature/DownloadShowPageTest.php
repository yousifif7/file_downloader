<?php

namespace Tests\Feature;

use App\Models\Download;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DownloadShowPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_download_page_shows_manual_download_button(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('downloads/test.mp4', 'video-content');

        $user = User::factory()->create();

        $download = Download::query()->create([
            'user_id' => $user->id,
            'source_url' => 'https://www.youtube.com/watch?v=test',
            'media_title' => 'Test video',
            'format' => 'best',
            'status' => Download::STATUS_COMPLETED,
            'file_path' => 'downloads/test.mp4',
            'file_size_bytes' => 1024,
            'completed_at' => now(),
            'expires_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($user)->get(route('downloads.show', $download));

        $response->assertOk();
        $response->assertSee('Download file');
        $response->assertSee('If nothing happens, tap the button above to start the download manually.');
        $response->assertSee(route('downloads.file', $download), false);
    }

    public function test_authenticated_user_can_download_completed_file_without_signed_url(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('downloads/test.mp4', 'video-content');

        $user = User::factory()->create();

        $download = Download::query()->create([
            'user_id' => $user->id,
            'source_url' => 'https://www.youtube.com/watch?v=test',
            'media_title' => 'Test video',
            'format' => 'best',
            'status' => Download::STATUS_COMPLETED,
            'file_path' => 'downloads/test.mp4',
            'file_size_bytes' => 1024,
            'completed_at' => now(),
            'expires_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($user)->get(route('downloads.file', $download));

        $response->assertOk();
        $response->assertDownload('Test video.mp4');
    }

    public function test_download_sanitizes_slashes_in_media_title(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('downloads/test.mp4', 'video-content');

        $user = User::factory()->create();

        $download = Download::query()->create([
            'user_id' => $user->id,
            'source_url' => 'https://www.youtube.com/watch?v=test',
            'media_title' => 'DrInSaNE - JUST A BOY/Lyrics',
            'format' => 'best',
            'status' => Download::STATUS_COMPLETED,
            'file_path' => 'downloads/test.mp4',
            'file_size_bytes' => 1024,
            'completed_at' => now(),
            'expires_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($user)->get(route('downloads.file', $download));

        $response->assertOk();
        $response->assertDownload('DrInSaNE - JUST A BOY-Lyrics.mp4');
    }
}
