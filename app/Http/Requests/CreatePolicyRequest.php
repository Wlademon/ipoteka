<?php

namespace App\Http\Requests;

/**
 * @OA\Schema(
 *     required={"activeFrom", "activeTo", "signedAt", "companyId", "insuredSum", "object", "subject"},
 *     schema="CreatePolicyRequest",
 *
 *     @OA\Property(property="ownerCode", type="string", example="STRAHOVKA", description="Код канала, откуда идут запросы для методов"),
 *     @OA\Property(property="options", ref="#/components/schemas/Options", description="Опции"),
 *     @OA\Property(property="activeFrom", type="date", example="2021-12-15", description="Дата начала действия договора страхования"),
 *     @OA\Property(property="activeTo", type="date", example="2022-12-14", description="Дата окончания действия договора страхования"),
 *     @OA\Property(property="insuredSum", type="integer", example=100000, description="Страховая сумма"),
 *     @OA\Property(property="programCode", type="string", example="VSK_TELEMED_001_01", description="Код программы"),
 *     @OA\Property(property="objects", ref="#/components/schemas/Objects", description="Объект страхования"),
 *     @OA\Property(property="subject", ref="#/components/schemas/Subject", description="Субъект страхования"),
 * )
 */
/**
 * @OA\Schema(
 *     required={"lastName", "firstName", "birthDate"},
 *     schema="Objects",
 *     type="array",
 *     @OA\Items(
 *         @OA\Property(property="lastName", type="string", example="Иванов", description="Фамилия застрахованного"),
 *         @OA\Property(property="firstName", type="string", example="Иван", description="Имя застрахованного"),
 *         @OA\Property(property="middleName", type="string", example="Иванович", description="Отчество застрахованного"),
 *         @OA\Property(property="birthDate", type="date", example="01-01-2010", description="Дата рождения застрахованного"),
 *         @OA\Property(property="gender", type="integer", example=0, description="Пол застрахованного: 1 - женский; 0 - мужской"),
 *         @OA\Property(property="docSeries", type="string", example="9999", description="Серия паспорта застрахованного"),
 *         @OA\Property(property="docNumber", type="string", example="999999", description="Номер паспорта застрахованного"),
 *         @OA\Property(property="docIssueDate", type="string", example="01-01-2000", description="Дата выдачи документа"),
 *         @OA\Property(property="docIssuePlace", type="string", example="УФМС", description="Кем выдан документ"),
 *         @OA\Property(property="city", type="string", example="Москва", description="Город проживания застрахованного"),
 *         @OA\Property(property="street", type="string", example="Ленина", description="Улица застрахованного"),
 *         @OA\Property(property="house", type="string", example="1", description="Дом застрахованного"),
 *         @OA\Property(property="phone", type="string", example="+79999999999", description="Телефонный нормер застрахованного (обязательно для Renisans)")
 *     )
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
 *     @OA\Property(property="lastName", type="string", example="Иванов", description="Фамилия страхователя"),
 *     @OA\Property(property="firstName", type="string", example="Иван", description="Имя страхователя"),
 *     @OA\Property(property="middleName", type="string", example="Иванович", description="Отчество страхователя (если имеется)"),
 *     @OA\Property(property="birthDate", type="date", example="01-01-1980", description="Дата рождения страхователя"),
 *     @OA\Property(property="gender", type="integer", example=0, description="Пол страхователя: 1 - женский; 0 - мужской"),
 *     @OA\Property(property="phone", type="string", example="+74342342234", description="Телефон страхователя"),
 *     @OA\Property(property="email", type="string", example="example@mail.com", description="Email страхователя"),
 *     @OA\Property(property="docSeries", type="string", example="2112", description="Серия паспорта страхователя"),
 *     @OA\Property(property="docNumber", type="string", example="543954", description="Номер паспорта страхователя"),
 *     @OA\Property(property="docIssueDate", type="date", example="01-01-2000", description="Дата выдача паспорта страхователя"),
 *     @OA\Property(property="docIssuePlace", type="string", example="Москва", description="Место выдачи"),
 *     @OA\Property(property="state", type="string", example="Московская область", description="Регион адреса регистрации страхователя"),
 *     @OA\Property(property="city", type="string", example="Москва", description="Город адреса регистрации страхователя"),
 *     @OA\Property(property="street", type="string", example="Ленина", description="Улица адреса регистрации страхователя"),
 *     @OA\Property(property="house", type="string", example="1", description="Дом адреса регистрации страхователя"),
 *     @OA\Property(property="block", type="string", example="корп 2", description="Корпус адреса регистрации страхователя"),
 *     @OA\Property(property="apartment", type="string", example="12", description="Номер квартиры адреса регистрации страхователя"),
 *     @OA\Property(property="kladr", type="string", example="5002700000000", description="Код КЛАДР адреса регистрации страхователя"),
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
