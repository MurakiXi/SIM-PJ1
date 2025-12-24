@extends('layouts.app')

@section('title', $item->name)
@section('css')
<link rel="stylesheet" href="{{ asset('css/show.css') }}">
@endsection

@section('content')

<div class="show__body">
    <div class="show-image">
        <img src="{{ $item->image_path ? asset('storage/'.$item->image_path) : '' }}" alt="{{ $item->name }}">
    </div>
    <div class="show__data">
        <h1>{{ $item->name }}</h1>
        @if(empty($item->buyer_id))
        <span class="show__data-price">¥{{ number_format($item->price) }}</span>
        <span class="show__data-price--p">(税込)</span>

        @endif
        @if(!empty($item->buyer_id))
        <p style="font-weight:bold;">Sold</p>
        @endif
        <div>
            <img class="show__data-fav" src="{{ asset('assets/images/heart-blank.png') }}" alt="いいね">
            <img class="show__data-fav" src="{{ asset('assets/images/comment.png') }}" alt="コメント">

            {{ $item->likes_count ?? 0 }}
            {{ $item->comments_count ?? 0 }}
        </div>
        <a href="{{ route('purchase.show', $item) }}" class="show__purchase">購入手続きへ</a>
        <h2>商品説明</h2>
        <div class="show__description">{{ $item->description }}</div>
        <h2>商品の情報</h2>
        <table class="show__data-table">
            <tr class="show__data-row">
                <th class="show__data-head">カテゴリー</th>
                @foreach($item->categories as $category)
                <td class="show__data-item">
                    {{ $category->name }}
                </td>
                @endforeach
            </tr>
            <tr class="show__data-table">
                <th class="show__data-head">商品の状態</th>
                <td class="show__data-item">
                    {{ $item->condition?->label() }}
                </td>
            </tr>
        </table>
        <h2>コメント({{ $item->comments_count ?? 0 }})</h2>

    </div>
</div>










<hr>

@endsection