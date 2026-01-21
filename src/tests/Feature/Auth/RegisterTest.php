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

    //ID1-1 名前が入力されていない場合、バリデーションメッセージが表示される
    public function test_register_name_required(): void
    {
        //2.名前を入力せずに他の必要項目を入力(準備)する
        $payload = $this->validRegisterPayload(['name' => '']);

        $response = $this->postRegister($payload);

        //1.会員登録ページを開く
        $response->assertStatus(302);
        //3.登録ボタンを押す
        $response->assertRedirect('/register');
        //期待挙動
        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください',
        ]);
        //usersテーブルにemail
        $this->assertDatabaseMissing('users', [
            'email' => $payload['email'],
        ]);

        $this->assertGuest();
    }

    //ID1-2 メールアドレスが入力されていない場合、バリデーションメッセージが表示される
    public function test_register_email_required(): void
    {
        //2.メールアドレスをを入力せずに他の必要項目を入力(準備)する
        $payload = $this->validRegisterPayload(['email' => '']);

        $response = $this->postRegister($payload);

        //1.会員登録ページを開く
        $response->assertStatus(302);
        //3.登録ボタンを押す
        $response->assertRedirect('/register');
        //期待挙動
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => $payload['email'],
        ]);

        $this->assertGuest();
    }


    //ID1-3 パスワードが入力されていない場合、バリデーションメッセージが表示される
    public function test_register_password_required(): void
    {
        //2.パスワードを入力せずに他の必要項目を入力(準備)する
        $payload = $this->validRegisterPayload(['password' => '', 'password_confirmation' => '']);

        $response = $this->postRegister($payload);

        //1.会員登録ページを開く
        $response->assertStatus(302);
        //3.登録ボタンを押す
        $response->assertRedirect('/register');

        //期待挙動
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => $payload['email'],
        ]);

        $this->assertGuest();
    }

    //ID1-4　パスワードが7文字以下の場合、バリデーションメッセージが表示される
    public function test_register_password_min_8(): void
    {
        //2.7文字以下のパスワードと他の必要項目を入力(準備)する
        $payload = $this->validRegisterPayload([
            'password' => '1234567',
            'password_confirmation' => '1234567',
        ]);

        $response = $this->postRegister($payload);

        //1.会員登録ページを開く
        $response->assertStatus(302);
        //3.登録ボタンを押す
        $response->assertRedirect('/register');

        //期待挙動
        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください',
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => $payload['email'],
        ]);

        $this->assertGuest();
    }

    //ID1-5　パスワードが確認用パスワードと一致しない場合、バリデーションメッセージが表示される
    public function test_register_password_confirmation_mismatch(): void
    {
        //2.確認用パスワードと異なるパスワードを入力し、他の必要項目も入力(準備)する
        $payload = $this->validRegisterPayload([
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ]);

        $response = $this->postRegister($payload);

        //1.会員登録ページを開く
        $response->assertStatus(302);
        //3.登録ボタンを押す
        $response->assertRedirect('/register');

        //期待挙動
        $response->assertSessionHasErrors([
            'password' => 'パスワードと一致しません',
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => $payload['email'],
        ]);

        $this->assertGuest();
    }

    //ID1-6　全ての項目が入力されている場合、会員情報が登録され、プロフィール設定画面に遷移される
    public function test_register_success_redirects_to_verify_page(): void
    {
        //2.全ての必要項目を正しく入力する
        $payload = $this->validRegisterPayload();

        //1.3.会員登録ページを開き、
        $response = $this
            ->withSession(['_token' => $payload['_token']])
            ->post('/register', $payload);

        $response->assertRedirect('/email/verify');

        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'email' => $payload['email'],
            'name'  => $payload['name'],
        ]);
    }
}
