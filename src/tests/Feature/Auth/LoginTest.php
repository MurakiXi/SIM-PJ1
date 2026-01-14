<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'name' => 'テスト太郎',
            'email' => 'taro@example.com',
            'password' => Hash::make('password123'),
        ], $overrides));
    }

    //ID2-1
    public function test_login_email_required(): void
    {
        $this->createUser();

        $response = $this
            ->from('/login')
            ->post('/login', [
                'email' => '',
                'password' => 'password123',
            ]);

        $response->assertStatus(302);
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    //ID2-2
    public function test_login_password_required(): void
    {
        $this->createUser();

        $response = $this
            ->from('/login')
            ->post('/login', [
                'email' => 'taro@example.com',
                'password' => '',
            ]);

        $response->assertStatus(302);
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('password');

        $this->assertGuest();
    }

    //ID2-3
    public function test_login_invalid_password_fails(): void
    {
        $this->createUser([
            'email' => 'taro@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this
            ->from('/login')
            ->post('/login', [
                'email' => 'taro@example.com',
                'password' => 'wrong-password',
            ]);

        $response->assertStatus(302);
        $response->assertRedirect('/login');

        $response->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    //ID2-4
    public function test_login_success(): void
    {
        $user = $this->createUser([
            'email' => 'taro@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this
            ->post('/login', [
                'email' => 'taro@example.com',
                'password' => 'password123',
            ]);

        $response->assertStatus(302);
        $this->assertAuthenticatedAs($user);
    }
}
