<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class VerifyTest extends TestCase
{
    use RefreshDatabase;

    //ID16-1
    public function test_register_sends_email_verification_notification(): void
    {
        $this->withoutExceptionHandling();

        Notification::fake();

        $payload = [
            'name' => 'テスト太郎',
            'email' => 'taro@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post('/register', $payload);

        $user = User::where('email', $payload['email'])->firstOrFail();

        Notification::assertSentTo($user, VerifyEmail::class);

        $this->assertAuthenticatedAs($user);

        $response->assertRedirect('/email/verify');
    }

    //ID16-2
    public function test_verify_notice_page_has_link_to_mailhog(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        $response = $this->actingAs($user)->get('/email/verify');

        $response->assertOk();

        $response->assertSee('<a class="verify__primary"', false);

        $response->assertSee('href="http://localhost:8025"', false);

        $response->assertSee('認証はこちらから');

        $response->assertSee('target="_blank"', false);

        $response->assertSee('rel="noopener"', false);
    }


    //ID16-3
    public function test_verify_email_redirects_to_profile_and_marks_verified(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        $response->assertRedirect(route('mypage.profile'));

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}
