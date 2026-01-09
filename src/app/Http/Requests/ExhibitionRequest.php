<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExhibitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'description' => ['required', 'string', 'max:255'],
            'image' => ['required', 'file', 'mimes:jpeg,png'],
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'condition' => ['required', 'integer', 'in:1,2,3,4'],
            'price' => ['required', 'integer', 'min:0'],
            'brand' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => '商品名',
            'description' => '商品説明',
            'image' => '商品画像',
            'category_ids' => '商品のカテゴリー',
            'condition' => '商品の状態',
            'price' => '商品価格',
            'brand' => 'ブランド名',
        ];
    }
}
