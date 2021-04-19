<?php
use OpenApi\Annotations as OA;
/**
 *  @OA\Schema(
 *     required={"success", "programCode", "data", "object"},
 *     schema="Directoryes",
 *     @OA\Property(property="success", type="boolaen", example="true", description="Успешное завершение запроса"),
 *     @OA\Property(property="data", description="Возвращаемые данные", type="array", required={},
 *        @OA\Items(
 *          schema="Directoryes_item",
 *           @OA\Property(property="description", type="string", example="Hello", description="Описание"),
 *           @OA\Property(property="risks", type="array", description="Риски", required={},
 *                @OA\Items(
 *                     @OA\Property(property="id", type="int", example="123", description="Идентификатор"),
 *                     @OA\Property(property="title", type="string", example="Hello", description="Заголовок"),
 *                     @OA\Property(property="description", type="string", example="Hello", description="Описание"),
 *                )
 *           ),
 *           @OA\Property(property="issues", type="array", description="Вопросы", required={},
 *                @OA\Items(
 *                     @OA\Property(property="title", type="string", example="Hello", description="Заголовок")
 *                )
 *           ),
 *           @OA\Property(property="conditions", type="object", description="Условия", required={}),
 *           @OA\Property(property="insuredSum", type="float", example=100000.05, description="Сумма страхования"),
 *           @OA\Property(property="companyCode", type="string", example="Gloria_1", description="Код компании"),
 *           @OA\Property(property="companyId", type="integer", example="123", description="Идентификатор компании"),
 *           @OA\Property(property="companyName", type="string", example="Глория", description="Наименование компании"),
 *           @OA\Property(property="isChild", type="boolean", example="0", description="Детский"),
 *           @OA\Property(property="isActive", type="boolean", example="1", description="Активный"),
 *           @OA\Property(property="programCode", type="string", example="GLORIA_12345_S", description="Код программы"),
 *           @OA\Property(property="programName", type="string", example="Базовая страховка", description="Наименование компании"),
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
 *        @OA\Property(property="id", type="int", example=100, description="Идентификатор"),
 *        @OA\Property(property="activeFrom", type="date", example="2021-10-10", description="Активность от"),
 *        @OA\Property(property="activeTo", type="date", example="2021-12-10", description="Активность до"),
 *        @OA\Property(property="objectsValue", type="array", description="Объекты страхования",
 *              @OA\Items()
 *          ),
 *        @OA\Property(property="objectFullName", type="array", description="Ф.И.О. застрахованных",
 *              @OA\Items()
 *          ),
 *        @OA\Property(property="companyCode", type="string", example="GLORIA_1", description="Код компании"),
 *        @OA\Property(property="signedAt", type="date", example="2021-10-5", description="Дата подписания"),
 *        @OA\Property(property="programName", type="string", example="Базовая страховка", description="Наименование программы"),
 *        @OA\Property(property="premium", type="float", example=1000.01, description="Премия"),
 *        @OA\Property(property="paymentStatus", type="int", example=1, description="Статус платежа"),
 *        @OA\Property(property="policyNumber", type="string", example="FRFFR-FDDFFDD-FG456-F344F", description="Номер полиса"),
 *        @OA\Property(property="trafficSource", type="string", example="http://ru", description="Источник"),
 *        @OA\Property(property="contractId", type="int", example=123, description="Идентификатор сделки"),
 *        @OA\Property(property="uwContractId", type="int", example=123, description="Идентификатор сделки uw"),
 *        @OA\Property(ref="#/components/schemas/Options"),
 *        @OA\Property(property="subjectValue", type="object", description="Субъект страхования"),
 *        @OA\Property(property="calcCoeff", type="object", type="object", description="Коэффициенты расчета")
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
