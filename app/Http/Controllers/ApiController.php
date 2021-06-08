<?php

namespace App\Http\Controllers;

use App\Http\Requests\CalculateRequest;
use App\Http\Requests\CreatePolicyRequest;
use App\Http\Resources\ContractResource;
use App\Models\Contracts;
use App\Models\Payment;
use App\Services\DriverService;
use App\Services\PayService\PayLinks;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Log;
use RuntimeException;
use Strahovka\Payment\PayService;

/**
 * Class ApiController
 * @package App\Http\Controllers
 */
class ApiController extends BaseController
{
    protected PayService $payService;
    protected DriverService $driverService;

    /**
     * Create a new controller instance.
     * @param  PayService  $payService
     * @param  DriverService  $driver
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
     *         description="Сохраняет договор в системе в статусе Проект и возвращает его
     *     contract_id",
     *         @OA\JsonContent(
     *              ref="#/components/schemas/CreatePolice"
     *          )
     *     )
     * )
     *
     * Сохраняет договор в системе в статусе Проект и возвращает его contract_id.
     *
     * @param  CreatePolicyRequest  $request
     * @return JsonResource
     * @throws Exception
     */
    public function postPolicyCreate(CreatePolicyRequest $request): JsonResource
    {
        return self::successResponse($this->driverService->savePolicy($request->validated()));
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
     *         description="Метод позволяет рассчитать (предварительную) премию по входящим
     *     пораметра",
     *         @OA\JsonContent(
     *              ref="#/components/schemas/CalculatedPolice"
     *          )
     *     )
     * )
     *
     * @param  CalculateRequest  $request
     * @return JsonResource
     * @throws Exception
     */
    public function postPolicyCalculate(CalculateRequest $request): JsonResource
    {
        return self::successResponse($this->driverService->calculate($request->validated()));
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
     * @param  Request  $request
     * @param  int  $contractId
     * @return JsonResource
     * @throws Exception
     */
    public function getPolicy(Request $request, int $contractId): JsonResource
    {
        Log::info("Find Contract with ID: {$contractId}");
        $contract = Contracts::query()->with(['objects', 'subject'])->findOrFail($contractId);

        return self::successResponse(new ContractResource($contract));
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
     *         description="Возвращает статус договора по ID полиса, полученного в ответе от
     *     /policy/save",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/StatusPolice"
     *         )
     *     )
     * )
     *
     * Возвращает статус договора по ID полиса, полученного в ответе от /policy/save
     *
     * @param  Request  $request
     * @param $contractId
     * @return JsonResource
     * @throws \Throwable
     * @internal param Contracts $contract
     */
    public function getPolicyStatus(Request $request, $contractId): JsonResource
    {
        Log::info("Find Contract with ID: {$contractId}");
        $contract = Contracts::findOrFail($contractId);

        return self::successResponse($this->driverService->getStatus($contract));
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
     *         description="Метод отправляет подтверждение оплаты и возвращает статус полиса. Метод
     *     необходимо вызывать для подтверждения факта оплаты полиса клиентом. Полис должен быть в
     *     статусе Проект (Draft). После вызова этого метода полис переводится в статус Действующий
     *     (Confirmed).",
     * @OA\JsonContent(ref="#/components/schemas/AcceptPayment")
     *     )
     * )
     *
     * Метод отправляет подтверждение оплаты и возвращает статус полиса. Метод необходимо вызывать
     *     для подтверждения факта оплаты полиса клиентом. Полис должен быть в статусе Проект
     *     (Draft). После вызова этого метода полис переводится в статус Действующий (Confirmed).
     * @param  Payment  $payment
     * @param $orderId
     * @return JsonResource
     * @throws Exception
     * @internal param Contracts $contract
     * @internal param $contractId
     */
    public function postPolicyAccept(Payment $payment, $orderId): JsonResource
    {
        Log::info("Find Payment with OrderID: {$orderId}");
        $res = $payment->where('order_id', $orderId)->firstOrFail();

        Log::info("Find Contract with ID: {$res->contract_id}");
        /** @var Contracts $contract */
        $contract = Contracts::with('company')->where(['id', $res->contract_id])->where(
            'status',
            Contracts::STATUS_DRAFT
        )->firstOrFail();

        Log::info("Start check payment status with OrderID: {$orderId}");
        $status = $this->payService->getOrderStatus($orderId);
        Log::info("Status: {$status['status']}");

        if (isset($status['isPayed']) && $status['isPayed']) {
            return self::successResponse($this->driverService->acceptPayment($contract));
        }

        throw new RuntimeException('Оплата заказа не обработана. Статус: '.$status["status"]);
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
     * @param  Request  $request
     * @param $contractId
     * @return JsonResource
     * @throws Exception
     * @internal param Payment $payment
     * @internal param $orderId
     * @internal param Contracts $contract
     */
    public function postPolicySend(Request $request, $contractId): JsonResource
    {
        Log::info("Find Contract with ID: {$contractId}");
        $contract = Contracts::findOrFail($contractId);

        $result = $this->driverService->sendMail($contract);
        Log::info("Response", [$result]);

        return self::successResponse($result);
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
     *     @OA\Response(response="200", description="Результат",
     *     @OA\JsonContent(ref="#/components/schemas/PolicyPayLink"))
     * )
     *
     * Возвращает url эквайринга.
     *
     * @param  Request  $request
     *
     * @param $contractId
     * @return JsonResource
     * @throws Exception
     * @internal param Contracts $contract
     */
    public function getPolicyPayLink(Request $request, $contractId): JsonResource
    {
        Log::info("Find Contract with ID: {$contractId}");
        $contract = Contracts::findOrFail($contractId);
        if (Payment::where('contract_id', $contract->id)->first()) {
            throw new RuntimeException(
                'Данный заказ уже обработан.', Response::HTTP_NOT_ACCEPTABLE
            );
        }
        try {
            $links = new PayLinks($request->query('successUrl'), $request->query('failUrl'));
            $linkResult = $this->driverService->getPayLink($contract, $links);
            Payment::createPayment($linkResult, $contract);
        } catch (Exception $e) {
            throw new RuntimeException(
                $e->getMessage().' (code: '.$e->getCode().')', Response::HTTP_NOT_ACCEPTABLE
            );
        }
        $result = ['url' => $linkResult->getUrl(), 'orderId' => $linkResult->getOrderId()];
        Log::info('Response', [$result]);

        return self::successResponse($result);
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
     * @param  Request  $request
     *
     * @param $contractId
     * @return JsonResource
     * @throws \Throwable
     * @internal param Contracts $contract
     */
    public function getPolicyPdf(Request $request, $contractId): JsonResource
    {
        Log::info("Find Contract with ID: {$contractId}");
        $isSample = filter_var($request->get('sample', false), FILTER_VALIDATE_BOOLEAN);

        $contract = Contracts::findOrFail($contractId);
        Log::info('Params', [$contract]);

        $response = $this->driverService->printPdf($contract, $isSample);
        Log::info('Policy generated!');

        return self::successResponse($response);
    }
}
