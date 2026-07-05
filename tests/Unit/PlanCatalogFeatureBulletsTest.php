<?php

namespace Tests\Unit;

use App\Models\Plan;
use App\Models\Platform;
use App\Services\PlanCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanCatalogFeatureBulletsTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_plan_features_exclude_paid_perks(): void
    {
        $plan = Plan::query()->create([
            'name' => 'Free',
            'slug' => 'free',
            'monthly_download_limit' => 5,
            'price_cents' => null,
            'is_active' => true,
        ]);

        $platform = Platform::query()->create([
            'slug' => 'youtube',
            'name' => 'YouTube',
            'extractor_class' => 'App\\Services\\Extractors\\YoutubeExtractor',
            'is_enabled' => true,
        ]);

        $plan->platforms()->attach($platform);

        $features = app(PlanCatalogService::class)->featureBullets($plan->load('platforms'));

        $this->assertSame('5 downloads / month', $features[0]['text']);
        $this->assertSame('YouTube', $features[1]['text']);
        $this->assertNull($features[1]['detail']);
        $this->assertSame('Download history & account dashboard', $features[2]['text']);
        $this->assertCount(3, $features);
    }

    public function test_paid_plan_features_include_email_support(): void
    {
        $plan = Plan::query()->create([
            'name' => 'Pro',
            'slug' => 'pro',
            'monthly_download_limit' => 50,
            'price_cents' => 250,
            'is_active' => true,
        ]);

        $features = app(PlanCatalogService::class)->featureBullets($plan);

        $this->assertSame('50 downloads / month', $features[0]['text']);
        $this->assertSame('Email support', $features[3]['text']);
        $this->assertCount(4, $features);
    }

    public function test_top_tier_gets_priority_support(): void
    {
        Plan::query()->create([
            'name' => 'Pro',
            'slug' => 'pro',
            'monthly_download_limit' => 50,
            'price_cents' => 250,
            'is_active' => true,
        ]);

        $ultimate = Plan::query()->create([
            'name' => 'Ultimate',
            'slug' => 'ultimate',
            'monthly_download_limit' => null,
            'price_cents' => 699,
            'is_active' => true,
        ]);

        $features = app(PlanCatalogService::class)->featureBullets($ultimate);

        $this->assertSame('Priority email support', $features[3]['text']);
        $this->assertSame('Faster responses on business days', $features[3]['detail']);
    }

    public function test_many_platforms_collapse_into_summary_with_detail(): void
    {
        $plan = Plan::query()->create([
            'name' => 'Ultimate',
            'slug' => 'ultimate',
            'monthly_download_limit' => null,
            'price_cents' => 699,
            'is_active' => true,
        ]);

        foreach (['YouTube', 'TikTok', 'Instagram', 'Twitter / X', 'Direct file', 'Facebook', 'LinkedIn'] as $index => $name) {
            $platform = Platform::query()->create([
                'slug' => 'platform-'.$index,
                'name' => $name,
                'extractor_class' => 'App\\Services\\Extractors\\YtDlpExtractor',
                'is_enabled' => true,
            ]);
            $plan->platforms()->attach($platform);
        }

        $features = app(PlanCatalogService::class)->featureBullets($plan->load('platforms'));

        $this->assertSame('All 7 supported platforms', $features[1]['text']);
        $this->assertStringContainsString('LinkedIn', $features[1]['detail']);
    }

    public function test_recommended_plan_uses_configured_slug(): void
    {
        Plan::query()->create([
            'name' => 'Pro',
            'slug' => 'pro',
            'monthly_download_limit' => 50,
            'price_cents' => 250,
            'is_active' => true,
        ]);

        Plan::query()->create([
            'name' => 'Ultimate',
            'slug' => 'ultimate',
            'monthly_download_limit' => null,
            'price_cents' => 699,
            'is_active' => true,
        ]);

        $catalog = app(PlanCatalogService::class);

        $this->assertSame('pro', $catalog->recommendedPlan()?->slug);
        $this->assertTrue($catalog->isRecommendedPlan(
            $catalog->activePlans()->firstWhere('slug', 'pro'),
            $catalog->activePlans(),
        ));
    }

    public function test_platform_availability_labels_free_paid_and_coming_soon(): void
    {
        $freePlan = Plan::query()->create([
            'name' => 'Free',
            'slug' => 'free',
            'monthly_download_limit' => 3,
            'price_cents' => null,
            'is_active' => true,
        ]);

        $proPlan = Plan::query()->create([
            'name' => 'Pro',
            'slug' => 'pro',
            'monthly_download_limit' => 50,
            'price_cents' => 250,
            'is_active' => true,
        ]);

        $youtube = Platform::query()->create([
            'slug' => 'youtube',
            'name' => 'YouTube',
            'extractor_class' => 'App\\Services\\Extractors\\YoutubeExtractor',
            'is_enabled' => true,
        ]);

        $instagram = Platform::query()->create([
            'slug' => 'instagram',
            'name' => 'Instagram',
            'extractor_class' => 'App\\Services\\Extractors\\YtDlpExtractor',
            'is_enabled' => true,
        ]);

        $facebook = Platform::query()->create([
            'slug' => 'facebook',
            'name' => 'Facebook',
            'extractor_class' => 'App\\Services\\Extractors\\YtDlpExtractor',
            'is_enabled' => false,
        ]);

        $freePlan->platforms()->attach($youtube);
        $proPlan->platforms()->attach([$youtube->id, $instagram->id]);

        $plans = Plan::query()->with('platforms')->where('is_active', true)->orderBy('id')->get();
        $catalog = app(PlanCatalogService::class);

        $this->assertSame('free', $catalog->platformAvailability($youtube, $freePlan, $plans)['status']);
        $this->assertSame('paid', $catalog->platformAvailability($instagram, $freePlan, $plans)['status']);
        $this->assertSame('Pro', $catalog->platformAvailability($instagram, $freePlan, $plans)['plan']->name);
        $this->assertSame('coming_soon', $catalog->platformAvailability($facebook, $freePlan, $plans)['status']);
    }
}
