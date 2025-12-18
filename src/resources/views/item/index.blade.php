@extends('layouts.app')

@section('title', '商品一覧')

@section('content')
@php
$tab = request('tab'); // null or 'mylist'
@endphp

<div style="display:flex; gap:12px; margin-bottom:12px;">
    <a href="{{ route('items.index', array_filter(['keyword' => request('keyword')])) }}">おすすめ</a>
    <a href="{{ route('items.index', array_filter(['tab' => 'mylist', 'keyword' => request('keyword')])) }}">マイリスト</a>
</div>

@if($tab === 'mylist' && auth()->guest())
<p>（未認証のため表示できません）</p>
@else
<div style="display:grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap:12px;">
    @forelse($items as $item)
    <div style="border:1px solid #ddd; padding:10px;">
        <a href="{{ route('items.show', $item) }}">
            <div style="font-weight:bold;">{{ $item->name }}</div>
        </a>

        <div>¥{{ number_format($item->price) }}</div>

        @if(!empty($item->buyer_id))
        <div style="margin-top:6px; font-weight:bold;">Sold</div>
        @endif

        <div style="margin-top:6px; font-size:12px; color:#666;">
            image: {{ $item->image_path ?? '(none)' }}
        </div>
    </div>
    @empty
    <p>商品がありません。</p>
    @endforelse
</div>
@endif
@endsection