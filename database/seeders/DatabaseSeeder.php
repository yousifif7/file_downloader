<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Platform;
use App\Models\Setting;
use App\Models\User;
use App\Services\Extractors\DirectFileExtractor;
use App\Services\Extractors\YtDlpExtractor;
use App\Services\Extractors\YoutubeExtractor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $freePlan = Plan::query()->updateOrCreate(
            ['slug' => 'free'],
            [
                'name' => 'Free',
                'monthly_download_limit' => 3,
                'price_cents' => null,
                'is_active' => true,
            ]
        );

        $platforms = [
            ['slug' => 'youtube', 'name' => 'YouTube', 'class' => YoutubeExtractor::class, 'enabled' => true],
            ['slug' => 'tiktok', 'name' => 'TikTok', 'class' => YtDlpExtractor::class, 'enabled' => true],
            ['slug' => 'twitter', 'name' => 'Twitter / X', 'class' => YtDlpExtractor::class, 'enabled' => true],
            ['slug' => 'direct', 'name' => 'Direct file', 'class' => DirectFileExtractor::class, 'enabled' => true],
            ['slug' => 'instagram', 'name' => 'Instagram', 'class' => YtDlpExtractor::class, 'enabled' => true],
            ['slug' => 'facebook', 'name' => 'Facebook', 'class' => YtDlpExtractor::class, 'enabled' => false],
            ['slug' => 'linkedin', 'name' => 'LinkedIn', 'class' => YtDlpExtractor::class, 'enabled' => false],
        ];

        foreach ($platforms as $platform) {
            $record = Platform::query()->updateOrCreate(
                ['slug' => $platform['slug']],
                [
                    'name' => $platform['name'],
                    'extractor_class' => $platform['class'],
                    'is_enabled' => $platform['enabled'],
                ]
            );

            if (in_array($platform['slug'], ['youtube', 'tiktok', 'twitter', 'direct'], true)) {
                $freePlan->platforms()->syncWithoutDetaching([$record->id]);
            }
        }

        $settings = [
            'free_tier_limit' => '3',
            'max_file_size_mb' => '500',
            'download_ttl_hours' => '24',
            'maintenance_mode' => '0',
            'maintenance_message' => '',
        ];

        foreach ($settings as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_admin' => true,
                'plan_id' => $freePlan->id,
                'downloads_this_month' => 0,
                'quota_reset_at' => now()->addMonth()->startOfMonth(),
            ]
        );
    }
}
