@extends('layouts.app')


@section('title', '会員登録')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endsection

@section('content')

<div class="auth__login-title">
    <h1>会員登録</h1>
</div>

<form action="{{ route('register') }}" class="auth__register-form" method="post">
    @csrf
    <div class="auth__register-title">ユーザー名</div>
    <div class="auth__register-input">
        <input type="text" ,name="name">
    </div>
    <div class="auth__register-title">メールアドレス</div>
    <div class="auth__register-input">
        <input type="text" ,name="email">
    </div>
    <div class="auth__login-title">パスワード</div>
    <div class="auth__login-input">
        <input type="password" ,name="password">
    </div>
    <div class="auth__login-title">確認用パスワード</div>
    <div class="auth__login-input">
        <input type="password" ,name="confirm">
    </div>
    <div class="auth__login-button">
        <button class="auth__login-button-submit" type="submit">
            登録する
        </button>
    </div>
</form>

<div class="auth__login-register">
    <a href="{{ route('login') }}"> ログインはこちら</a>
</div>
@endsection