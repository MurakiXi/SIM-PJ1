@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/profile.css') }}">
@endsection

@section('content')

<div class="profile__title">
    <h1>プロフィール設定</h1>
</div>

<form action="{{ route('mypage.update') }}" class="profile__form" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PATCH')
    <div class="profile__header">
        <div class="profile__header-image">
            @if(!empty($user->profile_image))
            <img
                src="{{ asset('storage/' . $user->profile_image) }}"
                alt="プロフィール画像">
            @else
            <div class="profile__header-image-placeholder" aria-hidden="true"></div>
            @endif
        </div>
        <input id="profile_image" type="file" name="profile_image"
            accept="image/jpeg,image/png" class="profile__file-input">
        <label for="profile_image" class="profile__image-choice-button">
            画像を選択する
        </label>

        @error('profile_image') <p class="form__error">{{ $message }}</p> @enderror
    </div>

    <div class="profile__inner">
        <label class="profile__label">ユーザー名</label>
        <div class="profile__form-item">
            <input type="text" class="profile__form-input" name="name" value="{{ old('name', $user->name) }}">
            @error('name') <p class="form__error">{{ $message }}</p> @enderror
        </div>
        <label class="profile__label">郵便番号</label>
        <div class="profile__form-item">
            <input type="text" class="profile__form-input" name="postal_code" value="{{ old('postal_code', optional($address)->postal_code) }}">
            @error('postal_code') <p class="form__error">{{ $message }}</p> @enderror
        </div>
        <label class="profile__label">住所</label>
        <div class="profile__form-item">
            <input type="text" class="profile__form-input" name="address" value="{{ old('address', optional($address)->address) }}">
            @error('address') <p class="form__error">{{ $message }}</p> @enderror
        </div>
        <label class="profile__label">建物名</label>
        <div class="profile__form-item">
            <input type="text" class="profile__form-input" name="building" value="{{ old('building', optional($address)->building) }}">
            @error('building') <p class="form__error">{{ $message }}</p> @enderror
        </div>
        <div class="profile__form-button">
            <button type="submit" class="profile__form-button-submit">更新する</button>
        </div>
    </div>
</form>

@endsection