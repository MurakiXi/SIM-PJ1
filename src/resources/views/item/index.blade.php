@extends('layouts.app')


@section('title', '商品一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/index.css') }}">
@endsection

@section('content')
@php
$tab = request('tab'); // null or 'mylist'
@endphp

<div class="list__head">
    <div class="list__head-recommend">
        <a href="{{ route('items.index', array_filter(['keyword' => request('keyword')])) }}">おすすめ</a>
    </div>
    <div class="list__head-mylist">
        <a href="{{ route('items.index', array_filter(['tab' => 'mylist', 'keyword' => request('keyword')])) }}">マイリスト</a>
    </div>
</div>
@guest
@if($tab === 'mylist')
<p>（未認証のため表示できません）</p>
@endif
@endguest

@if(!($tab === 'mylist' && auth()->guest()))
<div class="list__inner">
    @forelse($items as $item)
    <div class="list__card">
        <div class="list__card-image">
            <img src="{{ $item->image_path ? asset('storage/'.$item->image_path) : '' }}" alt="{{ $item->name }}">
        </div>
        <a class="list__card-name" href="{{ route('items.show', $item) }}">{{ $item->name }}</a>
        @if($item->status === 'sold')
        <div class="list__inner-sold">Sold</div>
        @endif

    </div>

    @empty
    <p>商品がありません。</p>
    @endforelse
</div>

@endif
@endsection