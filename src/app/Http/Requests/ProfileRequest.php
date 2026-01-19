<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */

    public function rules(): array
    {
        return [
            'profile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'name'          => ['required', 'string', 'max:255'],
            'postal_code'   => ['required', 'string', 'max:8', 'regex:/^\d{3}-?\d{4}$/'],
            'address'       => ['required', 'string', 'max:255'],
            'building'      => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            //
            'profile_image.image' => '画像ファイルを選択してください',
            'profile_image.mimes' => 'プロフィール画像は.jpegまたは.pngを選択してください',
            'name.required' => 'ユーザー名を入力してください',
            'name.max' => 'ユーザー名は20文字以内で入力してください',
            'postal_code.required' => '郵便番号を入力してください',
            'postal_code.regex' => '郵便番号は(3桁)-(4桁)の形で入力してください',
            'address.required' => '住所を入力してください',
        ];
    }

    public function attributes(): array
    {
        return [
            'profile_image' => 'プロフィール画像',
            'name' => 'ユーザー名',
            'postal_code' => '郵便番号',
            'address' => '住所',
            'building' => '建物名',
        ];
    }
}
