<?php

namespace App\Http\Requests;

/**
 * @OA\Schema(
 *     required={"code", "name", "isActive"},
 *     schema="CreateCompanyRequest",
 *
 * @OA\Property(property="code", type="string", example="INGOS", description="Код компании"),
 * @OA\Property(property="name", type="string", example="Ингос страхование", description="Название программы"),
 * @OA\Property(property="isActive", type="boolean", example=1, description="Флаг активности"),

 * )
 */

class CreateCompanyRequest extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            "code" => "required|unique:companies",
            "name" => 'required',
            "isActive" => 'required|in:0,1'
        ];
    }
}
