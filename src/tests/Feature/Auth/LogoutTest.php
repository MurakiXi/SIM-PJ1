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

    //ID3　ログアウトができる
    public function test_logout_success(): void
    {
        $user = $this->createUser();

        //1.ユーザーにログインをする
        $this->actingAs($user);
        $this->assertAuthenticatedAs($user);

        //2.ログアウトボタンを押す
        $response = $this
            ->from('/')
            ->post('/logout');
        //3.ログアウト処理が実行される
        $response->assertStatus(302);

        $this->assertGuest();
    }

    //(未認証状態でのログアウトで破綻しない)
    public function test_logout_as_guest_does_not_break(): void
    {
        //未認証状態でログアウト
        $response = $this->post('/logout');

        //302
        $response->assertStatus(302);

        $this->assertGuest();
    }
}
