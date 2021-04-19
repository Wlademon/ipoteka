<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class Request extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    public function messages()
    {
        $messages = [
            'required' => 'The :attribute field is required.',
        ];
        $validator = \Validator::make(Request::all(), $this->rules(), $messages);

        return $validator->errors()->all();
    }
}
