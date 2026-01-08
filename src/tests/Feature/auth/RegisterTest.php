<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    private function validRegisterPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'テスト太郎',
            'email' => 'taro@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            '_token' => 'test-token',
        ], $overrides);
    }


    public function test_register_success_redirects_to_profile_page(): void
    {
        $token = 'test-token';

        $payload = [
            'name' => 'テスト太郎',
            'email' => 'taro@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            '_token' => $token,
        ];

        $response = $this
            ->withSession(['_token' => $token])
            ->post('/register', $payload);

        $response->assertRedirect('/mypage/profile');

        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'email' => 'taro@example.com',
            'name'  => 'テスト太郎',
        ]);
    }


    public function test_register_name_required(): void
    {
        $token = 'test-token';
        $payload = $this->validRegisterPayload(['name' => '']);


        $response = $this
            ->withSession(['_token' => $token])
            ->post('/register', $payload);

        $response->assertStatus(302);

        $response->assertSessionHasErrors(['name']);


        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください',
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => 'taro@example.com',
        ]);

        $this->assertGuest();
    }

    public function test_register_email_required(): void
    {
        $token = 'test-token';
        $payload = $this->validRegisterPayload(['email' => '']);


        $response = $this
            ->withSession(['_token' => $token])
            ->post('/register', $payload);

        $response->assertStatus(302);

        $response->assertSessionHasErrors(['name']);


        $response->assertSessionHasErrors([
            'name' => 'メールアドレスを入力してください',
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => 'taro@example.com',
        ]);

        $this->assertGuest();
    }
}
