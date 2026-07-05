<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PlatformCookies;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PlatformCookiesUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        foreach (['youtube', 'instagram', 'facebook', 'linkedin'] as $platform) {
            $path = PlatformCookies::pathFor($platform);

            if (is_file($path)) {
                File::delete($path);
            }
        }

        parent::tearDown();
    }

    public function test_admin_can_upload_instagram_cookies(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $file = UploadedFile::fake()->createWithContent(
            'instagram-cookies.txt',
            ".instagram.com\tTRUE\t/\tFALSE\t0\tsessionid\tvalue\n"
        );

        $response = $this->actingAs($admin)->post(
            route('admin.settings.platform-cookies', 'instagram'),
            ['platform_cookies' => $file]
        );

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Instagram cookies updated.');
        $this->assertFileExists(PlatformCookies::pathFor('instagram'));
    }

    public function test_admin_cannot_upload_instagram_form_with_youtube_only_cookies(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $file = UploadedFile::fake()->createWithContent(
            'wrong-cookies.txt',
            ".youtube.com\tTRUE\t/\tFALSE\t0\tSAPISID\tvalue\n"
        );

        $response = $this->actingAs($admin)->from(route('admin.settings.edit'))->post(
            route('admin.settings.platform-cookies', 'instagram'),
            ['platform_cookies' => $file]
        );

        $response->assertRedirect(route('admin.settings.edit'));
        $response->assertSessionHasErrors('platform_cookies');
        $this->assertFileDoesNotExist(PlatformCookies::pathFor('instagram'));
    }
}
