@extends('layouts.app')

@section('content')
<div class="mypage__profile">
    @if(!empty($user->profile_image))
    <img src="{{ asset('storage/' . $user->profile_image) }}" alt="プロフィール画像">
    @endif
    <p>{{ $user->name }}</p>
    <a href="{{ route('mypage.profile') }}">プロフィールを編集</a>
</div>
<div style="margin: 16px 0;">
    <a href="{{ route('mypage', ['page' => 'sell']) }}">出品した商品</a>

    <a href="{{ route('mypage', ['page' => 'buy']) }}">購入した商品</a>
</div>

@if ($mode === 'buy')
<h2>購入した商品一覧</h2>

@if ($orders->isEmpty())
<p>購入済みの商品はまだありません。</p>
@else
<ul>
    @foreach ($orders as $order)
    <li style="margin-bottom: 8px;">
        {{ $order->item->name ?? '（商品情報なし）' }}
        / {{ $order->item->price ?? '' }}
        / {{ $order->paid_at ?? $order->created_at }}
    </li>
    @endforeach
</ul>

{{ $orders->links() }}
@endif

@else
<h2>出品した商品一覧</h2>

@if ($items->isEmpty())
<p>出品した商品はまだありません。</p>
@else
<ul>
    @foreach ($items as $item)
    <li style="margin-bottom: 8px;">
        {{ $item->name }}
        / {{ $item->price }}
        / {{ $item->status }}
    </li>
    @endforeach
</ul>

{{ $items->links() }}
@endif
@endif
@endsection