<?php

namespace Tests\Unit;

use App\Services\PlatformCookies;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PlatformCookiesTest extends TestCase
{
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

    public function test_it_validates_cookie_content_for_specific_platforms(): void
    {
        $this->assertTrue(PlatformCookies::validateContent('instagram', '.instagram.com	TRUE	/	FALSE	0	sessionid	value'));
        $this->assertTrue(PlatformCookies::validateContent('linkedin', 'lnkd.in	FALSE	/	FALSE	0	li_at	value'));
        $this->assertFalse(PlatformCookies::validateContent('youtube', '.instagram.com	TRUE	/	FALSE	0	sessionid	value'));
    }

    public function test_it_reports_platform_cookie_status(): void
    {
        PlatformCookies::store('instagram', '.instagram.com	TRUE	/	FALSE	0	sessionid	value');

        $status = PlatformCookies::status();

        $this->assertTrue($status['instagram']['exists']);
        $this->assertNotNull($status['instagram']['updated_at']);
        $this->assertFalse($status['linkedin']['exists']);
    }
}
