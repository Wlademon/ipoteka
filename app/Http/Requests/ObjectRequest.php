<?php

namespace App\Http\Requests;

use App\Models\Objects;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class ObjectRequest
 *
 * @OA\Schema(
 *     required={"contract_id", "type", "value"},
 *     schema="ObjectRequest",
 *     @OA\Property(property="contract_id", type="integer", example=1, description="Идентификатор сделки"),
 *     @OA\Property(property="product", type="string", example="property", description="Тип объекта страхования"),
 *     @OA\Property(property="number", type="string", example="123", description="Номер"),
 *     @OA\Property(property="external_id", type="string", example="123", description="Внешний идентификатор"),
 *     @OA\Property(property="uw_contract_id", type="string", example="123", description="Тип объекта"),
 *     @OA\Property(property="premium", type="string", example="123", description="Тип объекта"),
 *     @OA\Property(property="value", type="object", description="Данные объекта страхования",
 *          required={"city", "state", "house", "buildYear", "cityKladr"},
 *          @OA\Property(property="type", type="string", example="flat", description="Тип строения"),
 *          @OA\Property(property="buildYear", type="integer", example=1999, description="Год постройки"),
 *          @OA\Property(property="isWooden", type="boolean", example=true, description="Наличие деревянных перекрытий"),
 *          @OA\Property(property="area", type="number", example=99, description="Площадь"),
 *          @OA\Property(property="cityKladr", type="string", example="999999999999", description="Кладр"),
 *          @OA\Property(property="state", type="string", example="Москва", description="Регион"),
 *          @OA\Property(property="city", type="string", example="Москва", description="Город"),
 *          @OA\Property(property="street", type="string", example="Ленина", description="Улица"),
 *          @OA\Property(property="house", type="string", example="1", description="Дом"),
 *          @OA\Property(property="block", type="string", example="1", description="Блок"),
 *          @OA\Property(property="apartment", type="string", example="1", description="Квартира"),
 *     ),
 * )
 *
 * @package App\Http\Requests
 */
class ObjectRequest extends FormRequest
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
    public function rules()
    {
        return [
            'contract_id' => ['required', 'integer', 'exists:contracts,id'],
            'product' => ['required', 'string', 'in:' . Objects::types(true)],
            'value' => ['required', 'array'],
            'number' => ['nullable', 'string', 'max:255'],
            'external_id' => ['nullable', 'string', 'max:255'],
            'uw_contract_id' => ['nullable', 'integer', 'min:0'],
            'premium' => ['numeric', 'min:0'],
            // all
            'objects' => ['required'],
            'objects.life' => ['required_without:objects.property'],
            'objects.property' => ['required_without:objects.life'],
            'objects' => [],
            'objects' => [],
            'objects' => [],
            'objects' => [],
            'objects' => [],
            'objects' => [],
            'value.state' => ['required', 'string', 'max:255'],
            'value.city' => ['required', 'string', 'max:255'],
            'value.street' => ['nullable', 'string', 'max:255'],
            'value.house' => ['required', 'string', 'max:255'],
            'value.block' => ['nullable', 'string', 'max:255'],
            'value.apartment' => ['nullable', 'string', 'max:255'],
            // life
            'value.lastName' => ['required_if:product,' . Objects::TYPE_LIFE, 'prohibited_if:product,' .  Objects::TYPE_PROPERTY, 'string', 'max:255'],
            'value.firstName' => ['required_if:product,' . Objects::TYPE_LIFE, 'prohibited_if:product,' .  Objects::TYPE_PROPERTY, 'string', 'max:255'],
            'value.middleName' => ['required_if:product,' . Objects::TYPE_LIFE, 'prohibited_if:product,' .  Objects::TYPE_PROPERTY, 'string', 'max:255'],
            'value.birthDate' => ['required_if:product,' . Objects::TYPE_LIFE, 'prohibited_if:product,' .  Objects::TYPE_PROPERTY, 'date'],
            'value.gender' => ['required_if:product,' . Objects::TYPE_LIFE, 'prohibited_if:product,' .  Objects::TYPE_PROPERTY, 'integer', 'max:1', 'min:0'],
            'value.weight' => ['prohibited_if:product,' .  Objects::TYPE_PROPERTY, 'nullable', 'integer', 'min:0', 'max:500'],
            'value.height' => ['prohibited_if:product,' .  Objects::TYPE_PROPERTY, 'integer', 'min:0', 'max:300'],
            'value.phone' => ['required_if:product,' . Objects::TYPE_LIFE, 'prohibited_if:product,' .  Objects::TYPE_PROPERTY, 'string', 'regex:/^\+7\d{5,7}-\d{2}-\d{2}$/'],
            'value.email' => ['required_if:product,' . Objects::TYPE_LIFE, 'prohibited_if:product,' .  Objects::TYPE_PROPERTY, 'string', 'regex:/^\w+@(\w+\.)+\w+$/'],
            'value.docSeries' => ['required_if:product,' . Objects::TYPE_LIFE, 'prohibited_if:product,' .  Objects::TYPE_PROPERTY, 'integer', 'min:1000', 'max:9999'],
            'value.docNumber' => ['required_if:product,' . Objects::TYPE_LIFE, 'prohibited_if:product,' .  Objects::TYPE_PROPERTY, 'integer', 'min:100000', 'max:999999'],
            'value.docIssueDate' => ['required_if:product,' . Objects::TYPE_LIFE, 'prohibited_if:product,' .  Objects::TYPE_PROPERTY, 'date'],
            'value.docIssuePlace' => ['required_if:product,' . Objects::TYPE_LIFE, 'prohibited_if:product,' .  Objects::TYPE_PROPERTY, 'string', 'max:255'],
            'value.docIssuePlaceCode' => ['required_if:product,' . Objects::TYPE_LIFE, 'prohibited_if:product,' .  Objects::TYPE_PROPERTY, 'string', 'regex:/^\d{3,3}-\d{3,3}$/'],
            'value.kladr' => ['required_if:product,' . Objects::TYPE_LIFE, 'prohibited_if:product,' .  Objects::TYPE_PROPERTY, 'string', 'max:255'],
            'value.sports' => ['prohibited_if:product,' .  Objects::TYPE_PROPERTY, 'array'],
            'value.sports.*' => ['string', 'max:255'],
            'value.professions' => ['prohibited_if:product,' .  Objects::TYPE_PROPERTY, 'array'],
            'value.professions.*' => ['string', 'max:255'],
            // property
            'value.type' => ['prohibited_if:product,' .  Objects::TYPE_LIFE, 'nullable', 'string', 'in:' . Objects::propertyTypes(true)],
            'value.buildYear' => ['required_if:product,' . Objects::TYPE_PROPERTY, 'prohibited_if:product,' .  Objects::TYPE_LIFE, 'integer', 'min:1000', 'max:' . date('Y')],
            'value.isWooden' => ['nullable', 'prohibited_if:product,' .  Objects::TYPE_LIFE, 'boolean'],
            'value.area' => ['nullable', 'required_if:product,' . Objects::TYPE_PROPERTY, 'prohibited_if:product,' .  Objects::TYPE_LIFE, 'numeric', 'min:0'],
            'value.cityKladr' => ['required_if:product,' . Objects::TYPE_PROPERTY, 'prohibited_if:product,' .  Objects::TYPE_LIFE, 'string', 'max:255'],
        ];
    }
}
