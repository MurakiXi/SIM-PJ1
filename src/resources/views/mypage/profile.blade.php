@extends('layouts.app')

@section('content')

<div class="profile__title">
    <h1>プロフィール設定</h1>
</div>
<div class="profile__header">
    <img src="" alt="" class="profile__header-img">
    <div class="profile__header-button">
        <button class="profile__heder-button-choice">画像を選択する</button>
    </div>
</div>

<form action="" class="profile__form">
    <div class="profile__form-item">
        <label>ユーザー名</label>
        <input class="profile__form-input" name="name" value="{{ old('name', $user->name) }}">
    </div>
    <div class="profile__form-item">
        <label>郵便番号</label>
        <input class="profile__form-input" name="postal_code" value="{{ old('postal_code', optional($address)->postal_code) }}">
    </div>
    <div class="profile__form-item">
        <label>住所</label>
        <input class="profile__form-input" name="address" value="{{ old('address', optional($address)->address) }}">
    </div>
    <div class="profile__form-item">
        <label>建物名</label>
        <input class="profile__form-input" name="building" value="{{ old('building', optional($address)->building) }}">
    </div>
    <div class="profile__form-button">
        <button class="progile__form-button-submit">更新する</button>
    </div>
</form>
@endsection