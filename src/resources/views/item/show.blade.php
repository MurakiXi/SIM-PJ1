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
        @if(!($item->status === 'sold'))
        <span class="show__data-price">¥{{ number_format($item->price) }}</span>
        <span class="show__data-price--p">(税込)</span>
        @endif
        @if($item->status === 'sold')
        <p style="font-weight:bold;">Sold</p>
        @endif
        <table class="show__table">
            <tr class="show__table-row">
                <th class="show__table-item">
                    @auth
                    <form action="{{ route('items.like', $item) }}" method="post">
                        @csrf
                        <button type="submit" class="like-button">
                            <img class="show__data-fav"
                                src="{{ $isLiked
                                ? asset('assets/images/heart-red.png')
                                : asset('assets/images/heart-blank.png') }}"
                                alt="いいね">
                        </button>
                    </form>
                    @else
                    <img class="show__data-fav" src="{{ asset('assets/images/heart-blank.png') }}" alt="いいね">
                    @endauth
                </th>

                <th class="show__table-item">
                    <img class="show__data-fav" src="{{ asset('assets/images/comment.png') }}" alt="コメント">
                </th>
            </tr>

            <tr class="show__table-row">
                <th class="show__table-item">{{ $item->likes_count ?? 0 }}</th>
                <th class="show__table-item">{{ $item->comments_count ?? 0 }}</th>
            </tr>
        </table>

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
        @foreach($item->comments as $comment)
        <div class="show__comment">
            <div class="show__comment-user">{{ $comment->user->name }}</div>
            <div class="show__comment-body">{{ $comment->body }}</div>
        </div>
        @endforeach
    </div>
</div>










<hr>

@endsection