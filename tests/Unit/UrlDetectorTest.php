<?php

namespace Tests\Unit;

use App\Services\UrlDetector;
use PHPUnit\Framework\TestCase;

class UrlDetectorTest extends TestCase
{
    public function test_it_detects_instagram_mobile_hosts(): void
    {
        $detector = new UrlDetector();

        $this->assertSame('instagram', $detector->detect('https://instagram.com/reel/abc123'));
        $this->assertSame('instagram', $detector->detect('https://www.instagram.com/p/abc123/'));
        $this->assertSame('instagram', $detector->detect('https://m.instagram.com/reel/abc123/?utm_source=ig_web_copy_link'));
    }

    public function test_it_detects_linkedin_short_links(): void
    {
        $detector = new UrlDetector();

        $this->assertSame('linkedin', $detector->detect('https://lnkd.in/example-short-link'));
    }
}
