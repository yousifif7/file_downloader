<?php

namespace Tests\Unit;

use App\Models\Plan;
use App\Models\Platform;
use App\Models\User;
use App\Services\PlanCatalogService;
use App\Services\PlanPlatformAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanPlatformAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_plan_user_can_access_assigned_platforms_only(): void
    {
        $freePlan = Plan::query()->create([
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

        $instagram = Platform::query()->create([
            'name' => 'Instagram',
            'slug' => 'instagram',
            'extractor_class' => 'App\\Services\\Extractors\\YtDlpExtractor',
            'is_enabled' => true,
        ]);

        $freePlan->platforms()->sync([$youtube->id]);

        $user = User::factory()->create(['plan_id' => $freePlan->id]);
        $access = app(PlanPlatformAccessService::class);

        $this->assertTrue($access->userCanAccessPlatform($user, 'youtube'));
        $this->assertFalse($access->userCanAccessPlatform($user, 'instagram'));
    }

    public function test_catalog_labels_reflect_plan_configuration(): void
    {
        $plan = Plan::query()->create([
            'name' => 'Free',
            'slug' => 'free',
            'monthly_download_limit' => 10,
            'is_active' => true,
        ]);

        $youtube = Platform::query()->create([
            'name' => 'YouTube',
            'slug' => 'youtube',
            'extractor_class' => 'App\\Services\\Extractors\\YoutubeExtractor',
            'is_enabled' => true,
        ]);

        $plan->platforms()->sync([$youtube->id]);
        $plan->load('platforms');
        $catalog = app(PlanCatalogService::class);

        $this->assertSame('10 downloads / month', $catalog->monthlyLimitLabel($plan));
        $this->assertSame('YouTube', $catalog->platformNamesLabel($plan));
        $this->assertStringContainsString('10 downloads per month', $catalog->seoDescription($plan));
    }
}
