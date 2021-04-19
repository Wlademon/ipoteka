<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/**
 * @OA\Schema(
 *     required={"company_id", "program_code", "program_name"},
 *     schema="CreateProgramRequest",
 *
 * @OA\Property(property="company_id", type="integer", format="int64", example=1, description="Идентификатор компании"),
 * @OA\Property(property="program_code", type="string", example="absolut", description="Код программы"),
 * @OA\Property(property="program_name", type="string", example="Программа", description="Название программы"),
 * @OA\Property(property="is_active", type="boolean", example=1, description="Флаг активности"),
 * @OA\Property(property="insured_sum", type="integer", format="int64", example=200000, description="Страховая сумма"),
 * @OA\Property(property="description", type="string", example="Особенности и условия программы", description="Описание программы"),
 * @OA\Property(property="is_child", type="boolean", example="1", description="Для детей"),
 * @OA\Property(property="is_adult", type="boolean", example="1", description="Для взрослых"),
 * @OA\Property(property="is_family", type="boolean", example="1", description="Семейная"),
 * @OA\Property(
 *     property="issues",
 *     type="array",
 *     description="Страховые выплаты",
 *     example={
 *         {"title": "Выплата по риску инфекционное заболевание — 2 % от страховой суммы (20 000 рублей)"},
 *         {"title": "Выплата по риску смерть в результате инфекционного заболевания — 100 % страховой суммы (1 000 000 рублей)"},
 *         {"title": "Дата начала действия полиса (временная франшиза) — на 3 день от покупки"}
 *     },
 *     @OA\Items(
 *         type="object",
 *         @OA\Property(property="title", type="string")
 *     )
 * ),
 * @OA\Property(
 *     property="conditions",
 *     type="object",
 *     description="Условия",
 *     @OA\Property(property="timeFranchise", type="integer", format="int64", example=3),
 *     @OA\Property(property="minAges", type="integer", format="int64", example=7),
 *     @OA\Property(property="maxAges", type="integer", format="int64", example=70),
 *     @OA\Property(property="diseaseRefundPercent", type="integer", format="int64", example=2),
 *     @OA\Property(
 *          property="adultAges",
 *          type="object",
 *          example={
 *              "min": 18,
 *              "max": 65
 *          },
 *         @OA\Items(
 *              type="object",
 *              @OA\Property(property="min", type="integer", example=1),
 *              @OA\Property(property="max", type="integer", example=99)
 *         )
 *     ),
 *     @OA\Property(
 *          property="childAges",
 *          type="object",
 *          example={
 *              "min": 18,
 *              "max": 65
 *          },
 *         @OA\Items(
 *              type="object",
 *              @OA\Property(property="min", type="integer", example=1),
 *              @OA\Property(property="max", type="integer", example=99)
 *         )
 *     ),
 *     @OA\Property(property="periods", type="array",
 *          example={"15d","1m","2m","3m","4m","5m","6m","7m","8m","9m","10m","11m","12m"},
 *          @OA\Items(
 *              type="string",
 *          )
 *     ),
 * ),
 * @OA\Property(
 *     property="risks",
 *     type="array",
 *     description="Риски",
 *     @OA\Items(
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=99),
 *         @OA\Property(property="description", type="string", example="Описание"),
 *         @OA\Property(property="title", type="integer", example="Заголовок")
 *     )
 * ),
 * @OA\Property(
 *     property="matrix",
 *     type="array",
 *     description="Матрица расчета",
 *     @OA\Items(
 *         type="object",
 *         @OA\Property(property="tariff", type="object",
 *              @OA\Property(property="premium", type="integer", example=99)
 *          ),
 *     )
 * )
 * )
 */

class CreateProgramRequest extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'company_id' => 'required|exists:companies,id',
            'program_code' => 'required|string|min:1',
            'program_name' => 'required|string|min:1',
            'insured_sum' => 'required|numeric|min:1',
            'is_child' => 'required|boolean',
            'is_adult' => 'required|boolean',
            'is_family' => 'required|boolean',
            'is_active' => 'required|boolean',
            'program_uw_code' => 'required',
            'description' => 'nullable|string',
            'issues' => 'required|array',
            'issues.*.title' => 'required|string|min:1',
            'conditions' => 'required|array',
            'conditions.timeFranchise' => 'required|integer',
            'conditions.minAges' => 'required|integer',
            'conditions.maxAges' => 'required|integer',
            'conditions.diseaseRefundPercent' => 'nullable|integer',
            'conditions.adultAges' => 'required|array',
            'conditions.adultAges.min' => 'required|integer',
            'conditions.adultAges.max' => 'required|integer',
            'conditions.childAges' => 'required|array',
            'conditions.childAges.min' => 'required|integer',
            'conditions.childAges.max' => 'required|integer',
            'conditions.periods' => 'required|array',
            'conditions.periods.*' => ['required', Rule::in(["15d", "1m", "2m", "3m", "4m", "5m", "6m", "7m", "8m", "9m", "10m", "11m", "12m"])],
            'matrix' => 'required|array',
            'risks' => 'required|array'
        ];
    }
}
