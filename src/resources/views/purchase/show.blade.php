@extends('layouts.app')


@section('title', '購入')

@section('css')
<link rel="stylesheet" href="{{ asset('css/purchase.css') }}">
@endsection

@section('content')

<div class="purchase__grid">
    <form action="{{route('purchase.checkout',$item)}}" class="purchase__button" method="post">
        @csrf
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
                <select id="payment_method" name="payment_method" class="purchase__data-payment-select" required>
                    <option value="">選択してください</option>
                    <option value="convenience_store">コンビニ払い</option>
                    <option value="card">カード支払い</option>
                </select>
            </div>
            <div class="purchase__data-address">
                <div class="purchase__data-address-head">
                    <div class="purchase__data-title">配送先</div>
                    <a class="purchase__data-change">変更する</a>
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
                    <th class="purchase__list-head">
                        支払い方法
                    </th>
                    <td class="purchase__list-item">
                        <span id="payment_method_preview">選択してください</span>
                    </td>
                </tr>
            </table>
            <button class="purchase__button-submit" type="submit">
                購入する
            </button>
    </form>
</div>


@endsection

@section('js')
<script src="{{ asset('js/payment-select.js') }}" defer></script>
@endsection