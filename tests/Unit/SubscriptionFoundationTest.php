<?php

namespace Tests\Unit;

use App\Models\Plan;
use App\Models\User;
use App\Services\DownloadFailureClassifier;
use PHPUnit\Framework\TestCase;

class SubscriptionFoundationTest extends TestCase
{
    public function test_failure_classifier_groups_common_runtime_errors(): void
    {
        $classifier = new DownloadFailureClassifier();

        $this->assertSame('cookies', $classifier->classify('Cookies are required to complete this request.'));
        $this->assertSame('ffmpeg', $classifier->classify('FFmpeg merge failed after postprocess.'));
        $this->assertSame('deno', $classifier->classify('Deno runtime could not be started.'));
        $this->assertSame('unknown', $classifier->classify('Something unexpected happened.'));
    }

    public function test_user_subscription_status_label_falls_back_to_free_and_paid_states(): void
    {
        $freeUser = new User();
        $freeUser->setRelation('plan', new Plan(['slug' => 'free', 'name' => 'Free']));

        $paidUser = new User(['subscription_status' => User::SUBSCRIPTION_ACTIVE]);

        $this->assertSame('Free', $freeUser->subscriptionStatusLabel());
        $this->assertSame('Active', $paidUser->subscriptionStatusLabel());
        $this->assertTrue($paidUser->hasActiveSubscription());
    }
}
