<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */

    public function rules(): array
    {
        return [
            'payment_method' => ['required', Rule::in(['card', 'convenience_store'])],
            'address_id' => [
                'required',
                Rule::exists('addresses', 'id')->where(
                    fn($q) =>
                    $q->where('user_id', $this->user()->id)
                ),
            ],
        ];
    }
    public function messages(): array
    {
        return [
            'payment_method.required' => '支払い方法を選択してください',
            'payment_method.in'       => '支払い方法の選択が不正です',
            'address_id.required'     => '配送先を選択してください',
            'address_id.exists'       => '配送先の選択が不正です',
        ];
    }
}
