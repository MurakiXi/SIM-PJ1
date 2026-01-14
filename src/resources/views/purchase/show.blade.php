@extends('layouts.app')
@php
$selectedPayment = old('payment_method', '');
$paymentLabel = $paymentMethods[$selectedPayment] ?? $paymentMethods[''] ?? '選択してください';
@endphp

@section('title', '購入')

@section('css')
<link rel="stylesheet" href="{{ asset('css/purchase.css') }}">
@endsection

@section('content')

<div class="purchase__grid">
    <form action="{{route('purchase.checkout',$item)}}" class="purchase__button" method="post">
        @csrf
        @if($address)
        <input type="hidden" name="address_id" value="{{ $address->id }}">
        @endif
        <div class="purchase__data">
            <div class="purchase__data-item">
                <div class="purchase-data-image">
                    <img src="{{ $item->image_path ? asset('storage/'.$item->image_path) : '' }}" alt="{{ $item->name }}">
                </div>
                <h2>{{ $item->name }}</h2>
                <div class="show__data-price">¥{{ number_format($item->price) }}</div>
            </div>
            <div class="purchase__data-payment">
                <div class="purchase__data-title">支払い方法</div>
                <select id="payment_method" name="payment_method" required>
                    @foreach($paymentMethods as $value => $label)
                    <option value="{{ $value }}" {{ $selectedPayment === $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                    @endforeach
                </select>
                <span id="payment_method_preview">{{ $paymentLabel }}</span>
                @error('payment_method')
                <p class="error">{{ $message }}</p>
                @enderror

                @error('address_id')
                <p class="error">{{ $message }}</p>
                @enderror

                @error('purchase')
                <p class="error">{{ $message }}</p>
                @enderror

            </div>
            <div class="purchase__data-address">
                <div class="purchase__data-address-head">
                    <div class="purchase__data-title">配送先</div>
                    <a href="{{ route('purchase.address.edit', $item) }}" class="purchase__data-change">変更する</a>
                </div>
                <div class="purchase__data-address-content">
                    @if($address)
                    〒{{ $address->postal_code }}<br>
                    {{ $address->address }}<br>
                    {{ $address->building }}
                    @else
                    <p>配送先住所が未登録です。プロフィールから住所を登録してください。</p>
                    @endif
                </div>

            </div>
        </div>
        <div class="purchase__payment">

            <table class="purchase__list">
                <tr class="purchase__list-row">
                    <th class="purchase__list-head">
                        商品代金
                    </th>
                    <td class="purchase__list-item">
                        ¥{{ number_format($item->price) }}
                    </td>
                </tr>
                <tr class="purchase__list-row">
                    <th class="purchase__list-head">
                        支払い方法
                    </th>
                    <td class="purchase__list-item">
                        <span id="payment_method_preview">選択してください</span>
                    </td>
                </tr>
            </table>
            @if(!$address)
            <button class="purchase__button-submit" type="button" disabled>
                配送先住所を登録してください
            </button>
            @else
            <button class="purchase__button-submit" type="submit">
                購入する
            </button>
            @endif

        </div>
    </form>
</div>


@endsection

@section('js')
<script src="{{ asset('js/payment-select.js') }}" defer></script>
@endsection