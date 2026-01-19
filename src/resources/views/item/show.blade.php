@extends('layouts.app')

@section('title', $item->name)
@section('css')
<link rel="stylesheet" href="{{ asset('css/show.css') }}">
@endsection

@section('content')

<div class="show__body">
    <div class="show-image">
        <img class="show-image__img" src="{{ $item->image_path ? asset('storage/'.$item->image_path) : '' }}" alt="{{ $item->name }}">
    </div>
    <div class="show__data">
        <div class="show__data-name">
            {{ $item->name }}
        </div>
        @if(!empty($item->brand))
        <div class="show__data-brand">{{$item->brand}}</div>
        @endif
        @if($item->status === 'on_sale')
        <span class="show__data-price">¥{{ number_format($item->price) }}</span>
        <span class="show__data-price--p">(税込)</span>
        @elseif($item->status === 'processing')
        <div class="show__data-price">Processing</div>
        @elseif($item->status === 'sold')
        <div class="show__data-sold">Sold</div>
        @else
        <div class="show__data-unknown">Status: {{ $item->status }}</div>
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
                                ? asset('assets/images/heart-pink.png')
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

        @if($item->status === 'on_sale')
        <div class="show__purchase-button">
            <a href="{{ route('purchase.show', $item) }}" class="show__purchase">購入手続きへ</a>
            @elseif($item->status === 'processing')
            <div class="show__purchase-disabled">購入手続き中</div>
            @else
            <div class="show__purchase-disabled">売約済み</div>
            @endif
        </div>
        <div class="show__data-title">商品説明</div>
        <div class="show__description">{{ $item->description }}</div>
        <div class="show__data-title">商品の情報</div>
        <table class="show__data-table">
            <tr class="show__data-row">
                <th class="show__data-head">カテゴリー</th>
                @foreach($item->categories as $category)
                <td class="show__data-item-category">
                    {{ $category->name }}
                </td>
                @endforeach
            </tr>
            <tr class="show__data-table">
                <th class="show__data-head">商品の状態</th>
                <td class="show__data-item-condition">
                    {{ $item->condition?->label() }}
                </td>
            </tr>
        </table>
        <div class="show__data-title">コメント({{ $item->comments_count ?? 0 }})</div>
        @foreach($item->comments as $comment)
        <div class="show__comment">
            <div class="show__comment-profile">
                <div class="comment__image">
                    @if(!empty($comment->user->profile_image))
                    <img src="{{ asset('storage/' . $comment->user->profile_image) }}" alt="" class="comment__image-img">
                    @else
                    <div class="comment__image-blank" aria-hidden="true"></div>
                    @endif
                </div>
                <div class="show__comment-name">
                    <div class="show__comment-user">{{ $comment->user->name }}</div>
                </div>
            </div>
            <div class="show__comment-body">{{ $comment->body }}</div>
        </div>
        @endforeach
        @auth
        <form action="{{ route('items.comment', $item) }}" class="show__comment-form" method="POST">
            @csrf
            <div class="show__comment-title">商品へのコメント</div>
            <textarea name="body" class="show__comment-input">{{ old('body') }}</textarea>
            @error('body') <p class="form__error">{{ $message }}</p> @enderror
            <div class="show__comment-button">
                <button class="show__comment-button-submit" type="submit">コメントを送信する</button>
            </div>
        </form>
        @endauth
    </div>
</div>
@endsection