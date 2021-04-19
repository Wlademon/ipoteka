<?php

namespace App\Http\Requests;

/**
 * @OA\Schema(
 *     required={"code", "name", "uwLogin"},
 *     schema="CreateOwnerRequest",
 *
 * @OA\Property(property="code", type="string", example="test", description="Код источника"),
 * @OA\Property(property="name", type="string", example="testName", description="Имя источника"),
 * @OA\Property(property="uwLogin", type="string", example="1234", description="UWin логин"),
 * )
 */

class CreateOwnerRequest extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            "code" => "required",
            "name" => 'required',
            "uwLogin" => 'required'
        ];
    }
}
