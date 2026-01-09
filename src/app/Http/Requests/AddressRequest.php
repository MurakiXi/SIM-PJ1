<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
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
            'postal_code' => ['required', 'string', 'regex:/^\d{3}-\d{4}$/'],
            'address'     => ['required', 'string', 'max:255'],
            'building'    => ['nullable', 'string', 'max:255'],
        ];
    }
}
