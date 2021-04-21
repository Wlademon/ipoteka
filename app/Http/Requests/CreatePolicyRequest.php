<?php

namespace App\Http\Requests;

/**
 * @OA\Schema(
 *     required={"programCode", "activeFrom", "remainingDebt", "mortgageAgreementNumber", "isOwnership", "objects", "subject"},
 *     schema="CreatePolicyRequest",
 *     @OA\Property(property="ownerCode", type="string", example="STRAHOVKA", description="Код владельца системы. Если не задан, то STRAHOVKA"),
 *     @OA\Property(property="activeFrom", type="date", example="2021-12-15", description="Дата начала действия договора Ипотеки"),
 *     @OA\Property(property="activeTo", type="date", example="2022-12-14", description="Дата окончания действия договора Ипотеки"),
 *     @OA\Property(property="programCode", type="string", example="VSK_TELEMED_001_01", description="Идентификатор Кода программы"),
 *     @OA\Property(property="remainingDebt", type="string", example="1500000", description="Остаток  задолженности по договору ипотеки"),
 *     @OA\Property(property="mortgageAgreementNumber", type="string", example="ПАО Сбербанк", description="Номер договора ипотеки"),

 *     @OA\Property(property="objects", ref="#/components/schemas/Objects", description="Объект страхования"),
 *     @OA\Property(property="subject", ref="#/components/schemas/Subject", description="Субъект страхования"),
 * )
 */

/**
 * @OA\Schema(
 *     required={},
 *     schema="Objects",
 *     type="object",
 *     @OA\Property(property="property", ref="#/components/schemas/Property", description="Объект страхования"),
 *     @OA\Property(property="life", ref="#/components/schemas/Life", description="Объект страхования")
 * )
 */

/**
 * @OA\Schema(
 *     required={"buildYear", "area", "state", "city", "house", "apartment", "cityKladr"},
 *     schema="Property",
 *     type="object",
 *         @OA\Property(property="type", type="string", example="flat", description="Тип недвижимого имущества. Только flat - квартира. Если не указано, то равно flat."),
 *         @OA\Property(property="buildYear", type="int", example="2000", description="Минимальный год постройки дома"),
 *         @OA\Property(property="isWooden", type="bool", example="true", description="Признак 'Деревянные перекрытия' (true - Деревянные перекрытия). Если не указан, то false"),
 *         @OA\Property(property="area", type="int", example="55.3", description="Площадь квартиры"),
 *         @OA\Property(property="state", type="string", example="Московская область", description="Регион/область адреса объекта страхования"),
 *         @OA\Property(property="city", type="string", example="Москва", description="Населенный пункт адреса объекта страхования"),
 *         @OA\Property(property="street", type="string", example="Ленина", description="Улица адреса объекта страхования"),
 *         @OA\Property(property="house", type="string", example="1", description="Номер дома адреса объекта страхования"),
 *         @OA\Property(property="block", type="string", example="стр 1", description="Номер корпуса/строения адреса объекта страхования"),
 *         @OA\Property(property="apartment", type="string", example="23", description="Номер квартиры адреса объекта страхования"),
 *         @OA\Property(property="cityKladr", type="string", example="5002700000000", description="КЛАДР населенного пункта адреса объекта страхования"),
 * )
 */

/**
 * @OA\Schema(
 *     required={
 *     "lastName",
 *      "firstName",
 *      "birthDate",
 *      "gender",
 *      "phone",
 *      "email",
 *      "docSeries",
 *      "docNumber",
 *      "docIssueDate",
 *      "docIssuePlace",
 *      "docIssuePlaceCode",
 *      "state",
 *      "city",
 *      "house",
 *      "kladr"},
 *     schema="Life",
 *     type="object",
 *         @OA\Property(property="lastName", type="string", example="Сергеев", description="Фамилия застрахованного"),
 *         @OA\Property(property="firstName", type="string", example="Сергей", description="Имя застрахованного"),
 *         @OA\Property(property="middleName", type="string", example="Сергеевич", description="Отчество застрахованного"),
 *         @OA\Property(property="birthDate", type="date", example="1980-01-01", description="Дата рождения застрахованного"),
 *         @OA\Property(property="gender", type="int", example=0, description="Пол застрахованного (0 - мужской, 1 - женский)"),
 *         @OA\Property(property="weight", type="int", example="80", description="Вес застрахованного"),
 *         @OA\Property(property="height", type="int", example="185", description="Рост застрахованного"),
 *         @OA\Property(property="phone", type="string", example="+7616516-51-61", description="Телефон застрахованного"),
 *         @OA\Property(property="email", type="string", example="sdvkj@dfbvl.com", description="EMail застрахованного"),
 *         @OA\Property(property="docSeries", type="string", example="5616", description="Серия паспорта застрахованного"),
 *         @OA\Property(property="docNumber", type="string", example="516516", description="Номер паспорта застрахованного"),
 *         @OA\Property(property="docIssueDate", type="string", example="2020-01-01", description="Дата выдачи паспорта застрахованного"),
 *         @OA\Property(property="docIssuePlace", type="string", example="ОВД БЕЖИЦКОГО РАЙОНА Г. БРЯНСКА", description="Место выдачи паспорта застрахованного"),
 *         @OA\Property(property="docIssuePlaceCode", type="string", example="321-251", description="Код подразделения паспорта застрахованного"),
 *         @OA\Property(property="state", type="string", example="Москва", description="Регион/область адреса регистрации застрахованного"),
 *         @OA\Property(property="city", type="string", example="Москва", description="Населенный пункт адреса регистрации застрахованного"),
 *         @OA\Property(property="street", type="string", example="Андропова", description="Улица адреса регистрации застрахованного"),
 *         @OA\Property(property="house", type="string", example="1А", description="Номер дома адреса регистрации застрахованного"),
 *         @OA\Property(property="block", type="string", example="null", description="Номер корпуса/строения адреса регистрации застрахованного"),
 *         @OA\Property(property="apartment", type="string", example="null", description="Номер квартиры адреса регистрации застрахованного"),
 *         @OA\Property(property="kladr", type="string", example="7700000000000", description="КЛАДР населенного пункта адреса объекта страхования"),
 *        @OA\Property(property="sports", type="array", description="Виды спорта застрахованного (для СК Альфастрахование, берется из описания программ)",
 *              @OA\Items()
 *          ),
 *        @OA\Property(property="professions", type="array", description="Профессии застрахованного (для СК Альфастрахование, берется из описания программ)",
 *              @OA\Items()
 *          ),
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
 *     @OA\Property(property="lastName", type="string", example="Сергеев", description="Фамилия страхователя"),
 *     @OA\Property(property="firstName", type="string", example="Сергей", description="Имя страхователя"),
 *     @OA\Property(property="middleName", type="string", example="Сергеевич", description="Отчество страхователя (если имеется)"),
 *     @OA\Property(property="birthDate", type="date", example="1980-01-01", description="Дата рождения страхователя"),
 *     @OA\Property(property="gender", type="integer", example=0, description="Пол страхователя: 1 - женский; 0 - мужской"),
 *     @OA\Property(property="phone", type="string", example="+7616516-51-61", description="Телефон страхователя"),
 *     @OA\Property(property="email", type="string", example="sdvkj@dfbvl.com", description="Email страхователя"),
 *     @OA\Property(property="docSeries", type="string", example="5616", description="Серия паспорта страхователя"),
 *     @OA\Property(property="docNumber", type="string", example="516516", description="Номер паспорта страхователя"),
 *     @OA\Property(property="docIssueDate", type="date", example="2020-01-01", description="Дата выдача паспорта страхователя"),
 *     @OA\Property(property="docIssuePlace", type="string", example="ОВД БЕЖИЦКОГО РАЙОНА Г. БРЯНСКА", description="Место выдачи паспорта страхователя"),
 *     @OA\Property(property="docIssuePlaceCode", type="string", example="321-251", description="Код подразделения паспорта страхователя"),
 *     @OA\Property(property="state", type="string", example="Москва", description="Регион/область адреса регистрации страхователя"),
 *     @OA\Property(property="city", type="string", example="Москва", description="Населенный пункт адреса регистрации страхователя"),
 *     @OA\Property(property="street", type="string", example="Андропова", description="Улица адреса регистрации страхователя"),
 *     @OA\Property(property="house", type="string", example="1А", description="Номер дома адреса регистрации страхователя"),
 *     @OA\Property(property="block", type="string", example="null", description="Номер корпуса/строения адреса регистрации страхователя"),
 *     @OA\Property(property="apartment", type="string", example="null", description="Номер квартиры адреса регистрации страхователя"),
 *     @OA\Property(property="kladr", type="string", example="7700000000000", description="КЛАДР населенного пункта адреса регистрации страхователя"),
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
            'activeTo' => 'date',
            'ownerCode' => 'string',
            "remainingDebt" => "required|int",
            "mortgageAgreementNumber" => "required|string",
            "isOwnership" => "required|boolean",
            "mortgageeBank" => "string",
            'objects' => 'required|array',
            'subject' => 'required|array',
//
            // objects->property rules
            'objects.property.type' => 'string',
            'objects.property.buildYear' => 'required|numeric',
            'objects.property.isWooden' => 'boolean',
            'objects.property.area' => 'required|numeric',
            'objects.property.apartment' => 'required|string',
            'objects.property.cityKladr' => 'required|string',

            // objects->life rules

            'objects.life.firstName' => 'required|string',
            'objects.life.lastName' => 'required|string',
            'objects.life.middleName' => 'string|nullable',
            'objects.life.birthDate' => 'required|date',
            'objects.life.gender' => 'required|boolean',
            'objects.life.weight' => 'numeric|max:255',
            'objects.life.height' => 'numeric|max:255',
            'objects.life.phone' => 'required|string|regex:/^\+7(?:[0-9\-]){11,11}[0-9]$/m',
            'objects.life.docSeries' => 'integer|min:1000|max:9999',
            'objects.life.docNumber' => 'integer|min:100000|max:999999',
            'objects.life.docIssueDate' => 'string|date|before:today',
            'objects.life.docIssuePlace' => 'string',
            'objects.life.kladr' => 'required|string',
            'objects.life.apartment' => 'string|nullable',
            'objects.life.sports' => 'array',
            'objects.life.professions' => 'array',
            // Insured rules
            'objects.*.block' => 'string|nullable',
            'objects.*.city' => 'required|string',
            'objects.*.street' => 'required|string',
            'objects.*.house' => 'required',
            'objects.*.state' => 'required|string',
//            // Subject rules
            'subject.firstName' => 'required|string',
            'subject.lastName' => 'required|string',
            'subject.middleName' => 'string|nullable',
            'subject.birthDate' => ['required', 'string', 'date', 'after:' . date('Y-m-d', strtotime('-99 years')), 'before:' . date('Y-m-d', strtotime('-18 years'))],
            'subject.gender' => 'required|boolean',
            'subject.phone' => 'required|string|regex:/^\+7(?:[0-9\-]){11,11}[0-9]$/m',
            'subject.email' => 'required|string|max:255|email',
            'subject.docSeries' => 'required|integer|min:1000|max:9999',
            'subject.docNumber' => 'required|integer|min:100000|max:999999',
            'subject.docIssueDate' => ['required', 'string', 'date', 'before:today'],
            'subject.docIssuePlace' => 'required|string',
            'subject.state' => 'required|string',
            'subject.city' => 'required|string',
            'subject.street' => 'required|string',
            'subject.house' => 'required|string',
            'subject.kladr' => 'required|digits:13',
        ];
    }
}
