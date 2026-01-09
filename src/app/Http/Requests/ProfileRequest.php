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
        //must login
        return auth()->check();
    }


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
            'profile_image.image' => '画像ファイルを選択してください',
            'profile_image.mimes' => 'プロフィール画像は.jpegまたは.pngを選択してください',

            'name.required' => 'ユーザー名を入力してください',
            'name.max' => 'ユーザー名は20文字以内で入力してください',

            'postal_code.required' => '郵便番号を入力してください',
            'postal_code.regex' => '郵便番号は「123-4567」の形式で入力してください',

            'address.required' => '住所を入力してください',
        ];
    }

    public function messages(): array
    {
        return [
            //
            'name.required' => 'お名前を入力してください',
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