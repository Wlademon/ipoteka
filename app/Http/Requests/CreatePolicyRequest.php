<?php

namespace App\Http\Requests;

/**
 * @OA\Schema(
 *     required={"programCode", "activeFrom", "remainingDebt", "mortgageAgreementNumber", "isOwnership", "object", "subject"},
 *     schema="CreatePolicyRequest",
 *     @OA\Property(property="programCode", type="string", example="STRAHOVKA", description="Код канала, откуда идут запросы для методов"),
 *     @OA\Property(property="ownerCode", type="string", example="STRAHOVKA", description="Код канала, откуда идут запросы для методов"),
 *     @OA\Property(property="activeFrom", type="date", example="2021-12-15", description="Дата начала действия договора страхования"),
 *     @OA\Property(property="activeTo", type="date", example="2022-12-14", description="Дата окончания действия договора страхования"),
 *     @OA\Property(property="remainingDebt", type="integer", example=100000, description="Страховая сумма"),
 *     @OA\Property(property="mortgageAgreementNumber", type="integer", example=100000, description="Страховая сумма"),
 *     @OA\Property(property="isOwnership", type="string", example="VSK_TELEMED_001_01", description="Код программы"),
 *     @OA\Property(property="mortgageeBank", type="string", example="VSK_TELEMED_001_01", description="Код программы"),
 *     @OA\Property(property="objects", ref="#/components/schemas/Objects", description="Объект страхования"),
 *     @OA\Property(property="subject", ref="#/components/schemas/Subject", description="Субъект страхования"),
 * )
 */
/**
 * @OA\Schema(
 *     required={},
 *     schema="Objects",
 *     type="object",
 *     @OA\Property(property="property", type="object", ref="#/components/schemas/ObjectProperty", description="Объект страхования имущества"),
 *     @OA\Property(property="life", type="object", ref="#/components/schemas/ObjectLife", description="Объект страхования жизни"),
 * )
 */
/**
 * @OA\Schema(
 *     required={"buildYear","area","state","city","house","cityKladr"},
 *     schema="ObjectProperty",
 *     type="object",
 *     description="Страхование имущества",
 *     @OA\Property(property="type",  type="string", example="flat", description="Тип помещения"),
 *     @OA\Property(property="buildYear",  type="integer", example="2000", description="Год постройки"),
 *     @OA\Property(property="isWooden",  type="boolean", example="true", description="Наличие деревянных перекрытий"),
 *     @OA\Property(property="area",  type="float", example="55.3", description="Площадь"),
 *     @OA\Property(property="state",  type="string", example="Московская область", description="Регион"),
 *     @OA\Property(property="city",  type="string", example="Москва", description="Город"),
 *     @OA\Property(property="street",  type="string", example="Ленина", description="Улица"),
 *     @OA\Property(property="house",  type="string", example="1", description="Дом"),
 *     @OA\Property(property="block",  type="string", example="стр 1", description="Блок"),
 *     @OA\Property(property="apartment",  type="string", example="23", description="Квартира"),
 *     @OA\Property(property="cityKladr", type="string", example="5002700000000", description="Идентфикатор Кладр"),
 * )
 */

/**
 * @OA\Schema(
 *     required={"lastName","firstName","birthDate","gender","phone","email","docSeries","docNumber","docIssueDate","docIssuePlace","docIssuePlaceCode","state","city","house","kladr"},
 *     schema="ObjectLife",
 *     type="object",
 *     description="Страхование жизни",
 *     @OA\Property(property="lastName",  type="string", example="Сергеев", description="Фамилия"),
 *     @OA\Property(property="firstName",  type="string", example="Сергей", description="Имя"),
 *     @OA\Property(property="middleName",  type="string", example="Сергеевич", description="Отчество"),
 *     @OA\Property(property="birthDate",  type="string", example="1980-01-01", description="День рождения"),
 *     @OA\Property(property="gender",  type="integer", example=0, description="Пол"),
 *     @OA\Property(property="weight",  type="integer", example=80, description="Вес"),
 *     @OA\Property(property="height",  type="integer", example=185, description="Рост"),
 *     @OA\Property(property="phone",  type="string", example="+7616516-51-61", description="Телефон"),
 *     @OA\Property(property="email",  type="string", example="sdvkj@dfbvl.com", description="Почта"),
 *     @OA\Property(property="docSeries",  type="string", example="5616", description="Серия паспорта"),
 *     @OA\Property(property="docNumber",  type="string", example="516516", description="Номер паспорта"),
 *     @OA\Property(property="docIssueDate",  type="string", example="2020-01-01", description="Дата выдачи"),
 *     @OA\Property(property="docIssuePlace",  type="string", example="ОВД БЕЖИЦКОГО РАЙОНА Г. БРЯНСКА", description="Паспорт выдан"),
 *     @OA\Property(property="docIssuePlaceCode",  type="string", example="321-251", description="Код подразделения"),
 *     @OA\Property(property="state",  type="string", example="Москва", description="Регион"),
 *     @OA\Property(property="city",  type="string", example="Москва", description="Город"),
 *     @OA\Property(property="street",  type="string", example="Андропова", description="Улица"),
 *     @OA\Property(property="house",  type="string", example="1А", description="Дом"),
 *     @OA\Property(property="block",  type="string", example=null, description="Блок"),
 *     @OA\Property(property="apartment",  type="string", example=null, description="Квартира"),
 *     @OA\Property(property="kladr",  type="string", example="7700000000000", description="КЛАДР"),
 *     @OA\Property(property="sports",  type="array", @OA\Items(type="string", example="Бег"), description="Виды спорта"),
 *     @OA\Property(property="professions",  type="array", @OA\Items(type="string", example="Референт"), description="Профессии")
 * )
 */
/**
 * @OA\Schema(
 *     required={
 *     "lastName", "firstName", "birthDate", "phone", "email", "docSeries", "docNumber", "docIssueDate",
 *     "docIssuePlace", "state", "city", "street", "house", "kladr"
 *     },
 *     schema="Subject",
 *
 *     @OA\Property(property="lastName", type="string", example="Иванов", description="Фамилия"),
 *     @OA\Property(property="firstName", type="string", example="Иван", description="Имя"),
 *     @OA\Property(property="middleName", type="string", example="Иванович", description="Отчество (если имеется)"),
 *     @OA\Property(property="birthDate", type="date", example="01-01-1980", description="Дата рождения"),
 *     @OA\Property(property="gender", type="integer", example=0, description="Пол: 1 - женский; 0 - мужской"),
 *     @OA\Property(property="phone", type="string", example="+7616516-51-61", description="Телефон"),
 *     @OA\Property(property="email", type="string", example="example@mail.com", description="Email"),
 *     @OA\Property(property="docSeries", type="string", example="2112", description="Серия паспорта"),
 *     @OA\Property(property="docNumber", type="string", example="543954", description="Номер паспорта"),
 *     @OA\Property(property="docIssueDate", type="date", example="01-01-2000", description="Дата выдача паспорта"),
 *     @OA\Property(property="docIssuePlace", type="string", example="Москва", description="Место выдачи"),
 *     @OA\Property(property="state", type="string", example="Московская область", description="Регион адреса регистрации"),
 *     @OA\Property(property="city", type="string", example="Москва", description="Город адреса регистрации"),
 *     @OA\Property(property="street", type="string", example="Ленина", description="Улица адреса регистрации"),
 *     @OA\Property(property="house", type="string", example="1", description="Дом адреса регистрации"),
 *     @OA\Property(property="block", type="string", example="корп 2", description="Корпус адреса регистрации"),
 *     @OA\Property(property="apartment", type="string", example="12", description="Номер квартиры адреса регистрации"),
 *     @OA\Property(property="kladr", type="string", example="5002700000000", description="Код КЛАДР адреса регистрации"),
 * )
 */
/**
 * @OA\Schema(
 *     schema="Options",
 *     @OA\Property(property="trafficSource", type="array", example={"test"}, description="trafficSource",
 *         @OA\Items(type="string")
 *     )
 * )
 */
class CreatePolicyRequest extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            "programCode" => "required|string",
            'activeFrom' => 'required|date',
            'activeTo' => 'required|date',
            "insuredSum" => "required|numeric",
            'objects' => 'required|array',
            'subject' => 'required|array',

            // Insured rules
            'objects.*.firstName' => 'required|string',
            'objects.*.lastName' => 'required|string',
            'objects.*.middleName' => 'string|nullable',
            'objects.*.birthDate' => 'required|date',
            'objects.*.gender' => 'required|boolean',
            'objects.*.phone' => 'required|string|regex:/^\+7\d{5,7}\d{2}\d{2}$/',
            'objects.*.docSeries' => 'integer|min:1000|max:9999',
            'objects.*.docNumber' => 'integer|min:100000|max:999999',
            'objects.*.docIssueDate' => 'string|date|before:today',
            'objects.*.docIssuePlace' => 'string',
            'objects.*.city' => 'required|string',
            'objects.*.street' => 'required|string',
            'objects.*.house' => 'required',
            // Subject rules
            'subject.firstName' => 'required|string',
            'subject.lastName' => 'required|string',
            'subject.middleName' => 'string|nullable',
            'subject.birthDate' => ['required', 'string', 'date', 'after:' . date('Y-m-d', strtotime('-99 years')), 'before:' . date('Y-m-d', strtotime('-18 years'))],
            'subject.gender' => 'required|boolean',
            'subject.phone' => 'required|string|regex:/^\+7\d{5,7}\d{2}\d{2}$/',
            'subject.email' => 'required|string|max:255|email',
            'subject.docSeries' => 'required|integer|min:1000|max:9999',
            'subject.docNumber' => 'required|integer|min:100000|max:999999',
            'subject.docIssueDate' => ['required', 'string', 'date', 'before:today'],
            'subject.docIssuePlace' => 'required|string',
            'subject.state' => 'required|string',
            'subject.city' => 'required|string',
            'subject.street' => 'required|string',
            'subject.house' => 'required',
            'subject.kladr' => 'required|digits:13',
        ];
    }
}
