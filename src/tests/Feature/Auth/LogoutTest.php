<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        return User::factory()->create([
            'name' => 'テスト太郎',
            'email' => 'taro@example.com',
            'password' => Hash::make('password123'),
        ]);
    }

    //ID3
    public function test_logout_success(): void
    {
        $user = $this->createUser();

        $this->actingAs($user);
        $this->assertAuthenticatedAs($user);

        $response = $this
            ->from('/')
            ->post('/logout');

        $response->assertStatus(302);

        $this->assertGuest();
    }

    //ID3 guest
    public function test_logout_as_guest_does_not_break(): void
    {
        $response = $this->post('/logout');

        $response->assertStatus(302);

        $this->assertGuest();
    }
}
