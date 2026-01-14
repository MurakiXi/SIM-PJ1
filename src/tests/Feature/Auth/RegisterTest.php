<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    private function validRegisterPayload(array $overrides = []): array
    {
        $token = 'test-token';

        return array_merge([
            'name' => 'テスト太郎',
            'email' => 'taro@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            '_token' => $token,
        ], $overrides);
    }

    private function postRegister(array $overrides = [])
    {
        $payload = $this->validRegisterPayload($overrides);

        return $this
            ->from('/register')
            ->withSession(['_token' => $payload['_token']])
            ->post('/register', $payload);
    }

    // ID1-1
    public function test_register_name_required(): void
    {
        $payload = $this->validRegisterPayload(['name' => '']);

        $response = $this->postRegister(['name' => '']);

        $response->assertStatus(302);
        $response->assertRedirect('/register');

        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください',
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => $payload['email'],
        ]);

        $this->assertGuest();
    }

    // ID1-2
    public function test_register_email_required(): void
    {
        $payload = $this->validRegisterPayload(['email' => '']);

        $response = $this->postRegister(['email' => '']);

        $response->assertStatus(302);
        $response->assertRedirect('/register');

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => $payload['email'],
        ]);

        $this->assertGuest();
    }

    // ID1-3
    public function test_register_password_required(): void
    {
        $payload = $this->validRegisterPayload(['password' => '', 'password_confirmation' => '']);

        $response = $this->postRegister([
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/register');

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => $payload['email'],
        ]);

        $this->assertGuest();
    }

    // ID1-4
    public function test_register_password_min_8(): void
    {
        $payload = $this->validRegisterPayload([
            'password' => '1234567',
            'password_confirmation' => '1234567',
        ]);

        $response = $this->postRegister([
            'password' => '1234567',
            'password_confirmation' => '1234567',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/register');

        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください',
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => $payload['email'],
        ]);

        $this->assertGuest();
    }
    
    // ID1-5
    public function test_register_password_confirmation_mismatch(): void
    {
        $payload = $this->validRegisterPayload([
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ]);

        $response = $this->postRegister([
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/register');

        $response->assertSessionHasErrors([
            'password' => 'パスワードと一致しません',
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => $payload['email'],
        ]);

        $this->assertGuest();
    }

    //ID1-6
    public function test_register_success_redirects_to_profile_page(): void
    {
        $payload = $this->validRegisterPayload();

        $response = $this
            ->withSession(['_token' => $payload['_token']])
            ->post('/register', $payload);

        $response->assertRedirect('/mypage/profile');

        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'email' => $payload['email'],
            'name'  => $payload['name'],
        ]);
    }
}
