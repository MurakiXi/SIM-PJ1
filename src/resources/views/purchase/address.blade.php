@extends('layouts.app')


@section('title', '送付先住所変更')

@section('css')
<link rel="stylesheet" href="{{ asset('css/address.css') }}">
@endsection

@section('content')

<div class="edit-address__inner">
    <div class="edit-address__header">
        <h1>住所の変更</h1>
    </div>

    <form class="edit-address__form" action="{{ route('purchase.address.update', $item) }}" method="POST">
        @csrf
        @method('PATCH')

        <div class="edit-address__form-item">
            <div class="edit-address__form-label">郵便番号</div>
            <input type="text" inputmode="numeric" class="edit-address__form-input" name="postal_code" value="{{ old('postal_code', optional($address)->postal_code) }}">
            @error('postal_code') <p class="form__error">{{ $message }}</p> @enderror
        </div>
        <div class="edit-address__form-item">
            <div class="edit-address__form-label">住所</div>
            <input type="text" class="edit-address__form-input" name="address" value="{{old('address',optional($address)->address)}}">
            @error('address') <p class="form__error">{{ $message }}</p> @enderror
        </div>
        <div class="edit-address__form-item">
            <div class="edit-address__form-label">建物名</div>
            <input type="text" class="edit-address__form-input" name="building" value="{{old('building',optional($address)->building)}}">
            @error('building') <p class="form__error">{{ $message }}</p> @enderror
        </div>
        <div class="edit-address__button">
            <button class="edit-address__button-submit" type="submit">更新する</button>
        </div>
    </form>
</div>
@endsection