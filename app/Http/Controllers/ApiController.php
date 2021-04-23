<?php

namespace App\Http\Controllers;

use App\Drivers\Traits\LoggerTrait;
use App\Http\Requests\CalculateRequest;
use App\Http\Requests\CreatePolicyRequest;
use App\Models\Contracts;
use App\Models\Payments;
use App\Services\DriverService;
use App\Services\PayService\PayLinks;
use Exception;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RuntimeException;
use Strahovka\Payment\PayService;

/**
 * Class ApiController
 * @package App\Http\Controllers
 */
class ApiController extends BaseController
{
    use LoggerTrait;

    protected PayService $payService;
    protected DriverService $driverService;

    /**
     * Create a new controller instance.
     * @param PayService $payService
     * @param DriverService $driver
     */
    public function __construct(PayService $payService, DriverService $driver)
    {
        $this->payService = $payService;
        $this->driverService = $driver;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @OA\Post(
     *     path="/v1/policies",
     *     operationId="/v1/policies",
     *     summary="Создание полиса",
     *     tags={"Полисы"},
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/CreatePolicyRequest")
     *   ),
     *     @OA\Response(
     *         response="200",
     *         description="Сохраняет договор в системе в статусе Проект и возвращает его contract_id",
     *         @OA\JsonContent(
     *              ref="#/components/schemas/CreatePolice"
     *          )
     *     )
     * )
     *
     * Сохраняет договор в системе в статусе Проект и возвращает его contract_id.
     *
     * @param CreatePolicyRequest $request
     * @param DriverService $driver
     * @return ResponseFactory|Response
     * @throws Exception
     */
    public function postPolicyCreate(CreatePolicyRequest $request): Response
    {
        return $this->successResponse($this->driverService->savePolicy($request->validated()));
    }

    /**
     * @OA\Post(
     *     path="/v1/policies/calculate",
     *     operationId="/v1/policies/calculate",
     *     summary="Расчет полиса",
     *     tags={"Полисы"},
     *     @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/CalculateRequest")
     *   ),
     *     @OA\Response(
     *         response="200",
     *         description="Метод позволяет рассчитать (предварительную) премию по входящим пораметра",
     *         @OA\JsonContent(
     *              ref="#/components/schemas/CalculatedPolice"
     *          )
     *     )
     * )
     *
     * @param CalculateRequest $request
     * @return \Illuminate\Contracts\Foundation\Application|ResponseFactory|Response
     * @throws Exception
     */
    public function postCalculate(CalculateRequest $request): Response
    {
        return $this->successResponse($this->driverService->calculate($request->validated()));
    }

    /**
     * @OA\Get(
     *     path="/v1/policies/{contractId}",
     *     operationId="/v1/policies/{contractId}",
     *     summary="Информация о договоре",
     *     tags={"Полисы"},
     *     @OA\Parameter(
     *         name="contractId",
     *         in="path",
     *         description="Id договора",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Информация о сформированном полисе",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/GetPolice"
     *         )
     *     )
     * )
     *
     * Возвращает объект полиса по его ID.
     *
     * @param Request $request
     * @param $contractId
     * @return ResponseFactory|Response
     * @throws Exception
     */
    public function getPolicy(Request $request, $contractId): Response
    {
        self::log("Find Contract with ID: {$contractId}");
        $contract = Contracts::findOrFail($contractId);

        return $this->successResponse($contract);
    }

    /**
     * @OA\Get(
     *     path="/v1/policies/{contractId}/status",
     *     operationId="/v1/policies/{contractId}/status",
     *     summary="Получить статус договора",
     *     tags={"Полисы"},
     *     @OA\Parameter(
     *         name="contractId",
     *         in="path",
     *         description="Id договора",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Возвращает статус договора по ID полиса, полученного в ответе от /policy/save",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/StatusPolice"
     *         )
     *     )
     * )
     *
     * Возвращает статус договора по ID полиса, полученного в ответе от /policy/save
     *
     * @param Request $request
     * @param $contractId
     * @return ResponseFactory|Response
     * @internal param Contracts $contract
     */
    public function getPolicyStatus(Request $request, $contractId): Response
    {
        self::log("Find Contract with ID: {$contractId}");
        $contract = Contracts::findOrFail($contractId);

        return $this->successResponse($this->driverService->getStatus($contract));
    }

    /**
     * @OA\Post(
     *     path="/v1/policies/{orderId}/accept",
     *     operationId="/v1/policies/{orderId}/accept",
     *     summary="Подтверждение оплаты",
     *     tags={"Полисы"},
     *     @OA\Parameter(
     *         name="orderId",
     *         in="path",
     *         description="Id заказа",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Метод отправляет подтверждение оплаты и возвращает статус полиса. Метод необходимо вызывать для подтверждения факта оплаты полиса клиентом. Полис должен быть в статусе Проект (Draft). После вызова этого метода полис переводится в статус Действующий (Confirmed).",
     *         @OA\JsonContent(ref="#/components/schemas/AcceptPayment")
     *     )
     * )
     *
     * Метод отправляет подтверждение оплаты и возвращает статус полиса. Метод необходимо вызывать для подтверждения
     * факта оплаты полиса клиентом. Полис должен быть в статусе Проект (Draft). После вызова этого метода полис
     * переводится в статус Действующий (Confirmed).
     * @param Payments $payment
     * @param $orderId
     * @return ResponseFactory|Response
     * @throws Exception
     * @internal param Contracts $contract
     * @internal param $contractId
     */
    public function postPolicyAccept(Payments $payment, $orderId): Response
    {
        self::log("Find Payment with OrderID: {$orderId}");
        $res = $payment->whereOrderId($orderId)->firstOrFail();

        self::log("Find Contract with ID: {$res->contract_id}");
        $contract = Contracts::with('company')->whereId($res->contract_id)->whereStatus(
            Contracts::STATUS_DRAFT
        )->firstOrFail();

        self::log("Start check payment status with OrderID: {$orderId}");
        $status = $this->payService->getOrderStatus($orderId);
        self::log("Status: {$status['status']}");

        if (isset($status['isPayed']) && $status['isPayed']) {
            return $this->successResponse($this->driverService->acceptPayment($contract));
        }

        return $this->errorResponse(500, [], [], 'Оплата заказа не обработана. Статус: ' . $status["status"]);
    }

    /**
     * @OA\Post(
     *     path="/v1/policies/{contractId}/send",
     *     summary="Отправка полиса",
     *     tags={"Полисы"},
     *     @OA\Parameter(
     *         name="contractId",
     *         in="path",
     *         description="Id контракта",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Результат",
     *          @OA\JsonContent(ref="#/components/schemas/SendMail")
     *     )
     * )
     *
     * Метод отправляет письмо с полисом на почту клиенту
     * @param Request $request
     * @param $contractId
     * @return ResponseFactory|Response
     * @throws Exception
     * @internal param Payment $payment
     * @internal param $orderId
     * @internal param Contracts $contract
     */
    public function postPolicySend(Request $request, $contractId): Response
    {
        self::log("Find Contract with ID: {$contractId}");
        $contract = Contracts::findOrFail($contractId);

        $result = $this->driverService->sendMail($contract);
        self::log("Response", [$result]);

        return $this->successResponse($result);
    }

    /**
     * @OA\Get(
     *     path="/v1/policies/{contractId}/payLink",
     *     summary="Получение ссылки на эквайринг",
     *     tags={"Полисы"},
     *     @OA\Parameter(
     *         name="contractId",
     *         in="path",
     *         description="Id контракта",
     *         required=true,
     *         @OA\Schema(type="string", example="1")
     *     ),
     *     @OA\Parameter(
     *         name="successUrl",
     *         in="query",
     *         description="Полный URL страницы успешной оплаты",
     *         required=true,
     *         @OA\Schema(type="string", example="/pay/success")
     *     ),
     *     @OA\Parameter(
     *         name="failUrl",
     *         in="query",
     *         description="Полный URL страницы неудачной оплаты",
     *         required=true,
     *         @OA\Schema(type="string", example="/pay/fail")
     *     ),
     *     @OA\Response(response="200", description="Результат", @OA\JsonContent(ref="#/components/schemas/PolicyPayLink"))
     * )
     *
     * Возвращает url эквайринга.
     *
     * @param Request $request
     *
     * @param $contractId
     * @return ResponseFactory|Response|void
     * @throws Exception
     * @internal param Contracts $contract
     */
    public function getPolicyPayLink(Request $request, $contractId): Response
    {
        self::log("Find Contract with ID: {$contractId}");
        $contract = Contracts::findOrFail($contractId);
        if (Payments::whereContractId($contract->id)->first()) {
            self::abortLog('Данный заказ уже обработан (code: ' . Response::HTTP_BAD_REQUEST . ')', RuntimeException::class);
        }
        try {
            $links = new PayLinks($request->query('successUrl'), $request->query('failUrl'));
            $linkResult = $this->driverService->getPayLink($contract, $links);
            Payments::createPayment($linkResult, $contract);
        } catch (Exception $e) {
            self::abortLog($e->getMessage() . ' (code: ' . $e->getCode() . ')', RuntimeException::class);
        }
        $result = ['url' => $linkResult->getUrl(), 'orderId' => $linkResult->getOrderId()];
        self::log('Response', [$result]);

        return $this->successResponse($result);
    }

    /**
     * @OA\Get(
     *     path="/v1/policies/{contractId}/print",
     *     operationId="/v1/policies/{contractId}/print",
     *     summary="Получение ссылки на полис",
     *     tags={"Полисы"},
     *     @OA\Parameter(
     *         name="contractId",
     *         in="path",
     *         description="Id контракта",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sample",
     *         in="query",
     *         description="Флаг 'образец'",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Возвращает полис base64.",
     *         @OA\JsonContent(
     *              ref="#/components/schemas/PolicyPdf"
     *          )
     *     )
     * )
     *
     * Возвращает url эквайринга.
     *
     * @param Request $request
     *
     * @param $contractId
     * @return ResponseFactory|Response
     * @internal param Contracts $contract
     */
    public function getPolicyPdf(Request $request, $contractId): Response
    {
        self::log("Find Contract with ID: {$contractId}");
        $isSample = filter_var($request->get('sample', false), FILTER_VALIDATE_BOOLEAN);

        $contract = Contracts::findOrFail($contractId);
        self::log('Params', [$contract]);

        $response = $this->driverService->printPdf($contract, $isSample);
        self::log('Policy generated!');

        return $this->successResponse(['url' => $response]);
    }
}
