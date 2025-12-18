@extends('layouts.app')

@section('title', $item->name)

@section('content')
<h1>{{ $item->name }}</h1>

<p>¥{{ number_format($item->price) }}</p>

@if(!empty($item->buyer_id))
<p style="font-weight:bold;">Sold</p>
@endif

<p>ブランド：{{ $item->brand ?? '（なし）' }}</p>
<p>状態：{{ $item->condition_label ?? $item->condition }}</p>

<h2>カテゴリ</h2>
<ul>
    @foreach($item->categories as $category)
    <li>{{ $category->name }}</li>
    @endforeach
</ul>

<h2>説明</h2>
<p>{{ $item->description }}</p>

<hr>

<p>いいね：{{ $item->likes_count ?? 0 }} / コメント：{{ $item->comments_count ?? 0 }}</p>
@endsection