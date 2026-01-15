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
        //don't send verify mail really
        Notification::fake();

        $payload = [
            'name' => 'テスト太郎',
            'email' => 'taro@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        //register
        $response = $this->post('/register', $payload);

        //user exists on table
        $user = User::where('email', $payload['email'])->firstOrFail();

        //verify mail has sen
        Notification::assertSentTo($user, VerifyEmail::class);

        //authenticated
        $this->assertAuthenticatedAs($user);

        //redirect to verify page
        $response->assertRedirect('/email/verify');
    }

    //ID16-2
    public function test_verify_notice_page_has_link_to_mailhog(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/email/verify');

        $response->assertStatus(200);

        //link message exists
        $response->assertSee('認証はこちらから');

        //href points MailHog 8025
        $response->assertSee('href="http://localhost:8025"', false);
    }

    //ID16-3
    public function test_verify_email_redirects_to_profile_and_marks_verified(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        //make link for verification on Fortify/Laravel
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        //click link with login as user
        $response = $this->actingAs($user)->get($verificationUrl);

        //redirect to profile page
        $response->assertRedirect('/mypage/profile');

        //user verified on DB
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}
