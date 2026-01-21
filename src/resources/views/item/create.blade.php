@extends('layouts.app')

@section('title', '商品出品')

@section('css')
<link rel="stylesheet" href="{{ asset('css/sell.css') }}">
@endsection

@section('content')
@php
$selectedCondition = old('condition', '');
$conditionLabel = $condition[$selectedCondition] ?? $condition[''] ?? '選択してください';
@endphp


<div class="sell__inner">
    <div class="sell__title">
        <h1>商品の出品</h1>
    </div>
    <form action="{{ route('sell.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="sell__image">
            <div class="sell__detail-label">商品画像</div>

            <div class="sell__image-dropzone" id="sell-image-dropzone">
                <input id="sell-image-input" class="sell__image-input" type="file" name="image" accept=".jpg,.jpeg,.png,image/jpeg,image/png">
                <img id="sell-image-preview" class="sell__image-preview" alt="選択した画像のプレビュー" hidden>
                <div class="sell__image-actions">
                    <label id="sell-image-button" class="sell__image-button" for="sell-image-input">
                        画像を選択する
                    </label>

                    <button id="sell-image-remove" class="sell__image-remove" type="button" hidden>
                        画像を削除する
                    </button>
                </div>
            </div>

            @error('image') <p class="form__error">{{ $message }}</p> @enderror
        </div>
        <div class="sell__item-detail">
            商品の詳細
        </div>
        <div class="sell__item">
            <div class="sell__detail-label">カテゴリー</div>
            @php
            $oldCategoryIds = old('category_ids', []);
            if (!is_array($oldCategoryIds)) $oldCategoryIds = [];
            @endphp
            <div class="sell__categories">
                @foreach($categories as $category)
                @php $id = 'cat_' . $category->id; @endphp
                <input id="{{ $id }}" class="sell__category-check" type="checkbox" name="category_ids[]" value="{{ $category->id }}" {{ in_array($category->id, $oldCategoryIds, true) ? 'checked' : '' }}>
                <label class="sell__category-btn" for="{{ $id }}">
                    {{ $category->name }}
                </label>
                @endforeach
            </div>
            @error('category_ids') <p class="form__error">{{ $message }}</p> @enderror
            @error('category_ids.*') <p class="form__error">{{ $message }}</p> @enderror
        </div>
        <div class="sell__item">
            <div class="sell__detail-label">商品の状態</div>

            <div class="select-wrap">
                <select class="sell__item-condition" name="condition">
                    <option value="" disabled hidden {{ $selectedCondition ? '' : 'selected' }}>選択してください</option>
                    @foreach($conditions as $value => $label)
                    <option class="item-condition-choice" value="{{ $value }}" {{ (string)$value === (string)old('condition') ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                    @endforeach
                </select>
            </div>
            @error('condition') <p class="form__error">{{ $message }}</p> @enderror

        </div>
        <div class="sell__item-description">
            商品名と説明
        </div>

        <div class="sell__item">
            <div class="sell__description-label">商品名</div>
            <input class="sell__item-input" type="text" name="name" value="{{ old('name') }}">
            @error('name') <p class="form__error">{{ $message }}</p> @enderror
        </div>

        <div class="sell__item">
            <div class="sell__description-label">ブランド名</div>
            <input class="sell__item-input" type="text" name="brand" value="{{ old('brand') }}">
            @error('brand') <p class="form__error">{{ $message }}</p> @enderror
        </div>

        <div class="sell__item">
            <div class="sell__description-label">商品の説明</div>
            <textarea class="sell__item-textarea" name="description" maxlength="255" rows="6">{{ old('description') }}</textarea>
            @error('description') <p class="form__error">{{ $message }}</p> @enderror
        </div>

        <div class="sell__item-price">
            <div class="sell__description-label">販売価格</div>
            <span class="price-input__yen">¥</span>
            <input class="sell__item-input-price" type="number" name="price" min="0" step="1" value="{{ old('price') }}">
            @error('price') <p class="form__error">{{ $message }}</p> @enderror
        </div>

        <div class="sell__exhibit-button">
            <button class="sell__exhibit-button-submit" type="submit">出品する</button>
        </div>
    </form>
</div>
@endsection
@section('js')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const input = document.getElementById('sell-image-input');
        const preview = document.getElementById('sell-image-preview');
        const buttonLabel = document.getElementById('sell-image-button');
        const removeBtn = document.getElementById('sell-image-remove');
        const dropzone = document.getElementById('sell-image-dropzone');

        let currentObjectUrl = null;

        const prepareReselect = () => {
            input.value = '';
        };

        dropzone.addEventListener('click', () => {
            prepareReselect();
        });

        input.addEventListener('click', () => {
            prepareReselect();
        });

        input.addEventListener('change', () => {
            const file = input.files && input.files[0];
            if (!file) return;

            if (currentObjectUrl) {
                URL.revokeObjectURL(currentObjectUrl);
                currentObjectUrl = null;
            }

            currentObjectUrl = URL.createObjectURL(file);
            preview.src = currentObjectUrl;
            preview.hidden = false;

            buttonLabel.textContent = '画像を変更する';
            removeBtn.hidden = false;
        });

        removeBtn.addEventListener('click', () => {
            if (currentObjectUrl) {
                URL.revokeObjectURL(currentObjectUrl);
                currentObjectUrl = null;
            }

            preview.src = '';
            preview.hidden = true;

            input.value = '';

            buttonLabel.textContent = '画像を選択する';
            removeBtn.hidden = true;
        });
    });
</script>
@endsection