<?php

namespace Tests\Feature\Auth;

use App\Mail\WelcomeMail;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Plan::query()->create([
            'name' => 'Free',
            'slug' => 'free',
            'monthly_download_limit' => 5,
            'is_active' => true,
        ]);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        Mail::fake();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => '1',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('home', absolute: false).'#download');

        Mail::assertSent(WelcomeMail::class, fn ($mail) => $mail->hasTo('test@example.com'));
    }
}
