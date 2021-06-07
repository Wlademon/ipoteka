<?php

namespace App\Http\Requests;

use App\Models\Program;
use Illuminate\Validation\Rule;
use OpenApi\Annotations as OA;
/**
 * @OA\Schema(
 *     required={"activeFrom", "activeTo", "programCode", "remainingDebt", "objects", "isOwnership"},
 *     schema="CalculateRequest",
 *     @OA\Property(property="programCode", type="string", example="RENSINS_MORTGAGE_002_01", description="Код программы"),
 *     @OA\Property(property="activeFrom", type="date", example="2021-06-21", description="Дата начала действия договора Ипотеки"),
 *     @OA\Property(property="activeTo", type="date", example="2022-06-20", description="Дата окончания действия договора Ипотеки"),
 *     @OA\Property(property="remainingDebt", type="float", example=1500000, description="Остаток  задолженности по договору ипотеки"),
 *     @OA\Property(property="isOwnership", type="boolean", example=1, description="Признак наличия права собственности"),
 *     @OA\Property(property="mortgageeBank", type="string", example="СБЕРБАНК РОССИИ", description="Банк-залогодержатель (выгодоприобретатель)"),
 *     @OA\Property(property="objects", description="Объекты страхования", type="object",
 *        @OA\Property(property="property", type="object", description="Тип объекта страхования - Недвижимое имущество",
 *            required={"buildYear"},
 *            @OA\Property(property="type", type="string", example="flat", description="Тип недвижимого имущества."),
 *            @OA\Property(property="buildYear", type="integer", example=2000, description="Минимальный год постройки дома"),
 *            @OA\Property(property="isWooden", type="boolean", example=true, description="Признак Деревянные перекрытия (true - Деревянные перекрытия). Если не указан, то false")
 *        ),
 *        @OA\Property(property="life", type="object", description="Тип объекта страхования - Жизнь и здоровье",
 *            required={"birthDate", "gender"},
 *            @OA\Property(property="birthDate", type="date", example="01-01-1980", description="Дата рождения застрахованного"),
 *            @OA\Property(property="gender", type="integer", example=1, description="Пол застрахованного (0 - мужской, 1 - женский)"),
 *            @OA\Property(property="sports", type="array", description="Виды спорта застрахованного",
 *                @OA\Items(type="string", example="Бег")
 *            ),
 *            @OA\Property(property="professions", type="array", description="Профессии застрахованного",
 *                @OA\Items(type="string", example="Референт")
 *            )
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
            'programCode' => ['required', Rule::exists(Program::getTableName(), 'program_code')],
            'activeFrom' => ['required', 'date', 'after:now'],
            'activeTo' => ['required', 'date', 'after:activeFrom'],
            'remainingDebt' => ['required', 'numeric', 'min:0'],
            'isOwnership' => ['required', 'boolean'],
            'mortgageeBank' => ['required', 'string'],
            'objects' => ['required', 'array', 'min:1'],

            'objects.life' => ['required_without:objects.property'],
            'objects.life.birthDate' => ['required_with:objects.life', 'date', 'before_or_equal:-18 years'],
            'objects.life.gender' => ['required_with:objects.life', 'integer', 'min:0', 'max:1'],
            'objects.life.sports' => ['nullable', 'array'],
            'objects.life.sports.*' => ['string'],
            'objects.life.professions' => ['nullable'],
            'objects.life.professions.*' => ['string'],

            'objects.property' => ['required_without:objects.life'],
            'objects.property.type' => ['nullable', 'string', Rule::in(['flat'])],
            'objects.property.buildYear' => ['required_with:objects.property', 'integer', 'min:1000', 'max:' . date('Y')],
            'objects.property.isWooden' => ['nullable', 'boolean'],
        ];
    }
}
