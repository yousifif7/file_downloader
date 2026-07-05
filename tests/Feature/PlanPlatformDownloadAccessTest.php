<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Platform;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanPlatformDownloadAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_cannot_start_download_for_platform_not_on_plan(): void
    {
        $plan = Plan::query()->create([
            'name' => 'Free',
            'slug' => 'free',
            'monthly_download_limit' => 5,
            'is_active' => true,
        ]);

        $youtube = Platform::query()->create([
            'name' => 'YouTube',
            'slug' => 'youtube',
            'extractor_class' => 'App\\Services\\Extractors\\YoutubeExtractor',
            'is_enabled' => true,
        ]);

        Platform::query()->create([
            'name' => 'Instagram',
            'slug' => 'instagram',
            'extractor_class' => 'App\\Services\\Extractors\\YtDlpExtractor',
            'is_enabled' => true,
        ]);

        $plan->platforms()->sync([$youtube->id]);

        $user = User::factory()->create(['plan_id' => $plan->id]);

        $response = $this->actingAs($user)->post(route('downloads.store'), [
            'url' => 'https://www.instagram.com/reel/abc123/',
            'platform_slug' => 'instagram',
            'format' => 'best',
            'title' => 'Test reel',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('url');
    }
}
