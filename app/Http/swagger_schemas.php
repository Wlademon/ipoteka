<?php
use OpenApi\Annotations as OA;
/**
 *  @OA\Schema(
 *     required={"success", "programCode", "data", "object"},
 *     schema="Directoryes",
 *     @OA\Property(property="data", description="Возвращаемые данные", type="array", required={},
 *        @OA\Items(
 *          schema="Directoryes_item",
 *           @OA\Property(property="companyCode", type="string", example="ALFA_MSK", description="Код компании"),
 *           @OA\Property(property="companyName", type="string", example="АО «АльфаСтрахование»", description="Наименование компании"),
 *           @OA\Property(property="programCode", type="string", example="ALFA_MORTGAGE_001_01", description="Код программы"),
 *           @OA\Property(property="programName", type="string", example="Имущество", description="Наименование компании"),
 *           @OA\Property(property="programUwCode", type="string", example="0", description="Код программы в UW"),
 *           @OA\Property(property="isActive", type="boolean", example="true", description="Признак отображения программы на сайте"),
 *           @OA\Property(property="isRecommended", type="boolean", example="false", description="Признак рекомендованной программы на сайте"),
 *           @OA\Property(property="isProperty", type="boolean", example="true", description="Признак 'Страхование имущества'"),
 *           @OA\Property(property="isLife", type="boolean", example="false", description="Признак 'Страхование жизни'"),
 *           @OA\Property(property="isTitle", type="boolean", example="false", description="Признак 'Страхование титула'"),
 *           @OA\Property(property="description", type="string", example="Особенности и условия программы", description="Описание программы"),
 *
 *           @OA\Property(property="conditions", type="object", description="Условия страхования", required={}),
 *           @OA\Property(property="risks", type="object", description="Риски", required={}),
 *           @OA\Property(property="issues", type="object", description="Вопросы", required={}),
 *        )
 *     )
 * )
 */

/**
 *  @OA\Schema(
 *     required={"success", "data"},
 *     schema="Calc",
 *     @OA\Property(property="success", type="boolaen", example="true", description="Успешное завершение запроса"),
 *     @OA\Property(property="data", description="Возвращаемые данные", type="array", required={},
 *        @OA\Items(
 *           @OA\Property(property="premium", type="float", example=100.01, description="Премия"),
 *           @OA\Property(property="duration", type="int", example=10, description="Длительность"),
 *           @OA\Property(property="insuredSum", type="float", example=1000.01, description="Сумма страхования"),
 *           @OA\Property(property="programId", type="int", example=123, description="Идентификатор программы"),
 *           @OA\Property(property="calcCoeff", type="object", description="Коэффициенты расчета"),
 *        )
 *     )
 * )
 */

/**
 *  @OA\Schema(
 *     required={"success", "data"},
 *     schema="CreatePolice",
 *     @OA\Property(property="success", type="boolaen", example="true", description="Успешное завершение запроса"),
 *     @OA\Property(property="data", description="Возвращаемые данные", type="object", required={},
 *        @OA\Property(property="contractId", type="int", example=100, description="Идентификатор сделки"),
 *        @OA\Property(property="policyNumber", type="string", example="FRFFR-FDDFFDD-FG456-F344F", description="Номер полиса"),
 *        @OA\Property(property="premiumSum", type="float", example=1000.01, description="Премия"),
 *     )
 * )
 */

/**
 *  @OA\Schema(
 *     required={"success", "data"},
 *     schema="GetPolice",
 *     @OA\Property(property="success", type="boolaen", example="true", description="Успешное завершение запроса"),
 *     @OA\Property(property="data", description="Возвращаемые данные", type="object", required={},
 *        @OA\Property(property="ownerCode", type="string", example="STRAHOVKA", description="Код владельца"),
 *        @OA\Property(property="companyCode", type="string", example="ALFA_MSK", description="Код компании"),
 *        @OA\Property(property="programCode", type="string", example="ALFA_MORTGAGE_001_01", description="Идентификатор Кода программы"),
 *        @OA\Property(property="activeFrom", type="date", example="2021-09-15", description="Дата начала действия договора Ипотеки"),
 *        @OA\Property(property="activeTo", type="date", example="2022-09-14", description="Дата окончания действия договора Ипотеки"),
 *        @OA\Property(property="signetAt", type="date", example="2021-09-15", description="Дата заключения договора"),
 *        @OA\Property(property="remainingDebt", type="float", example=1500000, description="Остаток  задолженности по договору ипотеки"),
 *        @OA\Property(property="mortgageAgreementNumber", type="string", example="125-ИПО-1980", description="Номер договора ипотеки"),
 *        @OA\Property(property="isOwnerShip", type="boolean", example=true, description="Признак наличия права собственности (true - есть право собственности на имущество)"),
 *        @OA\Property(property="mortgageeBank", type="string", example="ПАО Сбербанк", description="Банк-залогодержатель (выгодоприобретатель)"),
 *        @OA\Property(property="premium", type="float", example=3000.5, description="Премия по договору"),
 *        @OA\Property(property="status", type="integer", example=2, description="Статус договора"),
 *        @OA\Property(property="objects", type="object", description="Объекты страхования",
 *              @OA\Property(property="property", description="Жилье", ref="#/components/schemas/ObjectProperty"),
 *              @OA\Property(property="life", description="Жизнь", ref="#/components/schemas/ObjectLife")
 *        ),
 *        @OA\Property(property="subject", ref="#/components/schemas/Subject", description="Субъект страхования")
 *     )
 * )
 */

/**
 *  @OA\Schema(
 *     required={"success", "data"},
 *     schema="CalculatedPolice",
 *     @OA\Property(property="success", type="boolaen", example="true", description="Успешное завершение запроса"),
 *     @OA\Property(property="data", description="Возвращаемые данные", type="object", required={"premiumSum", "contractId"},
 *        @OA\Property(property="contractId", type="integer", example="", description="id договора"),
 *        @OA\Property(property="premiumSum", type="float", example=3000.50, description="Общая премия по договору"),
 *        @OA\Property(property="lifePremium", type="float", example=2000.10, description="Премия по риску страхования жизни (если есть)"),
 *        @OA\Property(property="propertyPremium", type="float", example=1000.40, description="Премия по риску страхования недвижимого имущества (если есть)"),
 *     )
 * )
 */
/**
 *  @OA\Schema(
 *     required={"success", "data"},
 *     schema="StatusPolice",
 *     @OA\Property(property="success", type="boolaen", example="true", description="Успешное завершение запроса"),
 *     @OA\Property(property="data", description="Возвращаемые данные", type="object", required={},
 *        @OA\Property(property="status", type="string", example="Draft", description="Статус"),
 *     )
 * )
 */

/**
 *  @OA\Schema(
 *     required={"success", "data"},
 *     schema="AcceptPayment",
 *     @OA\Property(property="success", type="boolaen", example="true", description="Успешное завершение запроса"),
 *     @OA\Property(property="data", description="Возвращаемые данные", type="object", required={"id", "subject"},
 *        @OA\Property(property="id", type="int", example=123, description="Идентификатор сделки"),
 *        @OA\Property(property="subject", type="object", required={"email"},
 *              @OA\Property(property="email", type="string", example="test@.test.ru", description="Email"),
 *        )
 *     )
 * )
 */

/**
 *  @OA\Schema(
 *     required={"success", "data"},
 *     schema="SendMail",
 *     @OA\Property(property="success", type="boolaen", example="true", description="Успешное завершение запроса"),
 *     @OA\Property(property="data", description="Возвращаемые данные", type="object", required={"message"},
 *        @OA\Property(property="message", type="string", example="Email was sent", description="Сообщение"),
 *     )
 * )
 */

/**
 *  @OA\Schema(
 *     required={"success", "data"},
 *     schema="PolicyPayLink",
 *     @OA\Property(property="success", type="boolaen", example="true", description="Успешное завершение запроса"),
 *     @OA\Property(property="data", description="Возвращаемые данные", type="object", required={"url", "orderId"},
 *        @OA\Property(property="url", type="string", example="http://ru.ru", description="URL"),
 *        @OA\Property(property="orderId", type="string", example="12345", description="Номер транзакции")
 *     )
 * )
 */

/**
 *  @OA\Schema(
 *     required={"success", "data"},
 *     schema="PolicyPdf",
 *     @OA\Property(property="success", type="boolaen", example="true", description="Успешное завершение запроса"),
 *     @OA\Property(property="data", description="Возвращаемые данные", type="object", required={"url"},
 *        @OA\Property(property="url", type="string", example="sdasf23s342", description="BASE 64"),
 *     )
 * )
 */

/**
 *  @OA\Schema(
 *     required={"success", "data"},
 *     schema="Delete",
 *     @OA\Property(property="success", type="boolaen", example="true", description="Успешное завершение запроса"),
 *     @OA\Property(property="data", description="Возвращаемые данные", type="array", example={"Запись 6 успешно удалена"}, required={},
 *        @OA\Items(type="string")
 *     )
 * )
 */

/**
 *  @OA\Schema(
 *     required={"success", "data"},
 *     schema="GetList",
 *     @OA\Property(property="success", type="boolaen", example="true", description="Успешное завершение запроса"),
 *     @OA\Property(property="data", description="Возвращаемые данные", type="object", required={},
 *        @OA\Property(property="id", type="int", example=10, description="Идентификатор")
 *     )
 * )
 */
