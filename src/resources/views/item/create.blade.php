@extends('layouts.app')

@section('title', '商品出品')

@section('content')
<div class="sell">
    <h1>商品の出品</h1>

    <form action="{{ route('sell.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="sell__item">
            <label>商品画像</label><br>
            <input type="file" name="image" accept=".jpg,.jpeg,.png,image/jpeg,image/png">
            @error('image') <p class="form__error">{{ $message }}</p> @enderror
        </div>

        <div class="sell__item">
            <label>カテゴリー（複数選択可）</label><br>
            @php
            $oldCategoryIds = old('category_ids', []);
            if (!is_array($oldCategoryIds)) $oldCategoryIds = [];
            @endphp

            <div class="sell__categories">
                @foreach($categories as $category)
                <label style="display:inline-block; margin-right:12px; margin-bottom:8px;">
                    <input
                        type="checkbox"
                        name="category_ids[]"
                        value="{{ $category->id }}"
                        {{ in_array($category->id, $oldCategoryIds, true) ? 'checked' : '' }}>
                    {{ $category->name }}
                </label>
                @endforeach
            </div>

            @error('category_ids') <p class="form__error">{{ $message }}</p> @enderror
            @error('category_ids.*') <p class="form__error">{{ $message }}</p> @enderror
        </div>

        <div class="sell__item">
            <label>商品の状態</label><br>
            <select name="condition">
                <option value="">選択してください</option>
                @foreach($conditions as $value => $label)
                <option value="{{ $value }}" {{ (string)$value === (string)old('condition') ? 'selected' : '' }}>
                    {{ $label }}
                </option>
                @endforeach
            </select>
            @error('condition') <p class="form__error">{{ $message }}</p> @enderror
        </div>

        <div class="sell__item">
            <label>商品名</label><br>
            <input type="text" name="name" value="{{ old('name') }}">
            @error('name') <p class="form__error">{{ $message }}</p> @enderror
        </div>

        <div class="sell__item">
            <label>ブランド名（任意）</label><br>
            <input type="text" name="brand" value="{{ old('brand') }}">
            @error('brand') <p class="form__error">{{ $message }}</p> @enderror
        </div>

        <div class="sell__item">
            <label>商品の説明</label><br>
            <textarea name="description" maxlength="255" rows="6">{{ old('description') }}</textarea>
            @error('description') <p class="form__error">{{ $message }}</p> @enderror
        </div>

        <div class="sell__item">
            <label>販売価格</label><br>
            <input type="number" name="price" min="0" step="1" value="{{ old('price') }}">
            @error('price') <p class="form__error">{{ $message }}</p> @enderror
        </div>

        <div class="sell__actions">
            <button type="submit">出品する</button>
        </div>
    </form>
</div>
@endsection