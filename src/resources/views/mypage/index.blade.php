@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/mypage.css') }}">
@endsection


@section('content')
@php
$tab = request('page', 'sell');
@endphp

<div class="mypage__profile">
    <div class="mypage__header-image">
        @if(!empty($user->profile_image))
        <img
            src="{{ asset('storage/' . $user->profile_image) }}"
            alt="プロフィール画像">
        @else
        <div class="mypage__header-image-placeholder" aria-hidden="true"></div>
        @endif
    </div>

    <div class="mypage__profile-name">{{ $user->name }}</div>
    <a class="mypage__edit-profile" href="{{ route('mypage.profile') }}">プロフィールを編集</a>
</div>

<div class="profile__tab">
    <div class="profile__tab-exhibit">
        <a href="{{ route('mypage', ['page' => 'sell']) }}" class="profile__tab-link {{ $tab === 'sell' ? 'is-active' : '' }}" aria-current="{{ $tab === 'sell' ? 'page' : 'false' }}">出品した商品</a>
    </div>

    <div class="profile__tab-purchase">
        <a href="{{ route('mypage', ['page' => 'buy']) }}" class="profile__tab-link {{ $tab === 'buy' ? 'is-active' : '' }}" aria-current="{{ $tab === 'buy' ? 'page' : 'false' }}">購入した商品</a>
    </div>
</div>


@if ($mode === 'buy')

@if ($orders->isEmpty())
<p>購入済みの商品はまだありません。</p>
@else
<ul>
    @foreach ($orders as $order)
    <li class="profile__trade-list">
        {{ $order->item->name ?? '（商品情報なし）' }}
        / {{ $order->item->price ?? '' }}
        / {{ $order->paid_at ?? $order->created_at }}
    </li>
    @endforeach
</ul>

{{ $orders->links() }}
@endif

@else

@if ($items->isEmpty())
<p>出品した商品はまだありません。</p>
@else
<div class="profile__list">
    @foreach ($items as $item)
    <div class="profile__list-card">
        <div class="profile__list-image">
            <img src="{{ $item->image_path ? asset('storage/'.$item->image_path) : '' }}" alt="{{ $item->name }}">
        </div>
        <div class="profile__list-name">
            {{ $item->name }}
        </div>
    </div>
    @endforeach
</div>
{{ $items->links() }}
@endif
@endif
@endsection