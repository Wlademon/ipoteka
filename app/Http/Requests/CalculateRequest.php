<?php

namespace App\Http\Requests;

/**
 * @OA\Schema(
 *     required={"activeFrom", "activeTo", "programCode", "insuredSum", "object"},
 *     schema="CalculateRequest",
 *
 *     @OA\Property(property="activeFrom", type="date", example="2021-09-15", description="Дата начала действия договора страхования"),
 *     @OA\Property(property="activeTo", type="date", example="2021-10-14", description="Дата окончания действия договора страхования"),
 *     @OA\Property(property="programCode", type="string", example="VSK_TELEMED_001_01", description="Код программы"),
 *     @OA\Property(property="insuredSum", type="integer", example=100000, description="Страховая сумма"),
 *     @OA\Property(property="objects", description="Объект страхования", type="array", required={"birthDate"},
 *        @OA\Items(
 *           @OA\Property(property="birthDate", type="date", example="01-01-2010", description="Дата рождения застрахованного"),
 *        )
 *     )
 * )
 */
class CalculateRequest extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            "activeFrom" => 'required|date',
            "activeTo" => 'required|date',
            "programCode" => "required",
            "insuredSum" => "required|numeric",
            'objects' => 'required|array',
            // Insured rules
            'objects.*.birthDate' => 'required|date',
        ];
    }
}
