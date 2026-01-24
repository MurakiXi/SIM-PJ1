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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:255'],
            'image' => ['required', 'file', 'mimes:jpeg,png', 'max:4096'],
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

    public function messages(): array
    {
        return [
            //
            'name.required' => '商品名を入力してください。',
            'name.max' => '商品名は255文字以内で入力してください。',
            'description.required' => '商品の説明を入力してください。',
            'description.max' => '商品の説明は255文字以内で入力してください。',
            'image.required' => '商品画像を指定してください。',
            'image.mimes' => '商品画像は、jpegまたは.pngを選択してください',
            'image.max' => '画像データは4MB以内にしてください。',
            'category_ids.required' => '商品のカテゴリーを選択してください。',
            'condition.required' => '商品の状態を指定してください。',
            'price.required' => '販売価格を入力してください',
        ];
    }
}
