@extends('layouts.app')

@section('content')

<div class="profile__title">
    <h1>プロフィール設定</h1>
</div>

<form action="{{ route('mypage.update') }}" class="profile__form" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PATCH')
    <div class="profile__header">

        @if(!empty($user->profile_image))
        <img src="{{ asset('storage/' . $user->profile_image) }}" class="profile__header-image" alt="プロフィール画像">
        @endif

        <input id="profile_image" type="file" name="profile_image"
            accept="image/jpeg,image/png" style="display:none;">

        <label for="profile_image" class="profile__header-button-choice">
            画像を選択する
        </label>
        @error('profile_image') <p class="form__error">{{ $message }}</p> @enderror
    </div>

    <div class="profile__form-item">
        <label>ユーザー名</label>
        <input type="text" class="profile__form-input" name="name" value="{{ old('name', $user->name) }}">
        @error('name') <p class="form__error">{{ $message }}</p> @enderror
    </div>

    <div class="profile__form-item">
        <label>郵便番号</label>
        <input type="text" class="profile__form-input" name="postal_code" value="{{ old('postal_code', optional($address)->postal_code) }}">
        @error('postal_code') <p class="form__error">{{ $message }}</p> @enderror
    </div>

    <div class="profile__form-item">
        <label>住所</label>
        <input type="text" class="profile__form-input" name="address" value="{{ old('address', optional($address)->address) }}">
        @error('address') <p class="form__error">{{ $message }}</p> @enderror
    </div>

    <div class="profile__form-item">
        <label>建物名</label>
        <input type="text" class="profile__form-input" name="building" value="{{ old('building', optional($address)->building) }}">
        @error('building') <p class="form__error">{{ $message }}</p> @enderror
    </div>

    <div class="profile__form-button">
        <button type="submit" class="profile__form-button-submit">更新する</button>
    </div>
</form>

@endsection