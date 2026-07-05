<?php

namespace Tests\Unit;

use App\Services\Extractors\YtDlpExtractor;
use PHPUnit\Framework\TestCase;

class YtDlpExtractorUrlCleanupTest extends TestCase
{
    public function test_it_strips_tiktok_query_parameters_for_clean_urls(): void
    {
        $extractor = new TestableYtDlpExtractor();

        $cleaned = $extractor->clean('https://www.tiktok.com/@demo/video/123456?is_from_webapp=1&sender_device=pc');

        $this->assertSame('https://www.tiktok.com/@demo/video/123456', $cleaned);
    }

    public function test_it_removes_utm_tracking_from_instagram_urls_but_keeps_other_query_values(): void
    {
        $extractor = new TestableYtDlpExtractor();

        $cleaned = $extractor->clean('https://m.instagram.com/reel/abc123/?utm_source=ig_web_copy_link&img_index=1');

        $this->assertSame('https://m.instagram.com/reel/abc123/?img_index=1', $cleaned);
    }
}

class TestableYtDlpExtractor extends YtDlpExtractor
{
    public function clean(string $url): string
    {
        return $this->cleanUrl($url);
    }
}
