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

    //ID2-1　メールアドレスが入力されていない場合、バリデーションメッセージが表示される
    public function test_login_email_required(): void
    {
        $this->createUser();

        //2.メールアドレスを入力せずに他の必要項目を入力(準備)する
        $response = $this
            ->from('/login')
            ->post('/login', [
                'email' => '',
                'password' => 'password123',
            ]);

        //1.ログインページを開く
        $response->assertStatus(302);
        //3.ログインボタンを押す
        $response->assertRedirect('/login');

        //期待挙動
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);


        $this->assertGuest();
    }

    //ID2-2
    public function test_login_password_required(): void
    {
        $this->createUser();

        //2.パスワードを入力せずに他の必要項目を入力(準備)する
        $response = $this
            ->from('/login')
            ->post('/login', [
                'email' => 'taro@example.com',
                'password' => '',
            ]);

        //1.ログインページを開く
        $response->assertStatus(302);
        //3.ログインボタンを押す
        $response->assertRedirect('/login');

        //期待挙動
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);


        $this->assertGuest();
    }

    //ID2-3
    public function test_login_invalid_password_fails(): void
    {
        //2.必要項目に登録されていない情報を入力(準備)する
        $this->createUser([
            'email' => 'taro@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this
            ->from('/login')
            ->post('/login', [
                'email' => 'jiro@example.com',
                'password' => 'wrong-password',
            ]);

        //1.ログインページを開く
        $response->assertStatus(302);
        //3.ログインボタンを押す
        $response->assertRedirect('/login');

        //期待挙動

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません'
        ]);

        $this->assertGuest();
    }

    //ID2-4
    public function test_login_success(): void
    {
        //2.全ての必要項目を入力(準備)する
        $user = $this->createUser([
            'email' => 'taro@example.com',
            'password' => Hash::make('password123'),
        ]);

        //1.ログインページを開く
        $this->get('/login')->assertOk();
        //3.ログインボタンを押す
        $response = $this
            ->post('/login', [
                'email' => 'taro@example.com',
                'password' => 'password123',
            ]);

        $response->assertStatus(302);
        $this->assertAuthenticatedAs($user);
    }
}
