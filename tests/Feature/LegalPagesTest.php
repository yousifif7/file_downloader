<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_legal_pages_are_publicly_accessible(): void
    {
        $this->get(route('terms'))->assertOk()->assertSee('Terms of Service');
        $this->get(route('privacy'))->assertOk()->assertSee('Privacy Policy');
        $this->get(route('refund'))->assertOk()->assertSee('Refund Policy');
        $this->get(route('support'))->assertOk()->assertSee('Log in to contact support');
    }

    public function test_sitemap_includes_legal_pages(): void
    {
        $response = $this->get(route('sitemap'));

        $response->assertOk();
        $response->assertSee(route('terms'), false);
        $response->assertSee(route('privacy'), false);
        $response->assertSee(route('refund'), false);
        $response->assertSee(route('support'), false);
    }

    public function test_registration_requires_terms_acceptance(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('terms');
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }
}
