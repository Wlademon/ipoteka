<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class Request
 *
 * @package App\Http\Requests
 */
abstract class Request extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        $messages = [
            'required' => 'The :attribute field is required.',
        ];
        $validator = \Validator::make(Request::all(), $this->rules(), $messages);

        return $validator->errors()->all();
    }
}
