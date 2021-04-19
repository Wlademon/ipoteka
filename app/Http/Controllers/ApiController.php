<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreatePolicyRequest;
use App\Models\Contracts;
use App\Models\Payments;
use App\Services\DriverService;
use Exception;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Strahovka\Payment\PayService;

class ApiController extends BaseController
{

    protected $payService;
    protected $driverService;

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
        return $this->successResponse($this->driverService->savePolicy($request));
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
    public function getPolicy(Request $request, $contractId)
    {
        Log::info(__METHOD__ . ". Find Contract with ID: {$contractId}");
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
    public function getPolicyStatus(Request $request, $contractId)
    {
        Log::info(__METHOD__ . ". Find Contract with ID: {$contractId}");
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
    public function postPolicyAccept(Payments $payment, $orderId)
    {
        Log::info(__METHOD__ . ". Find Payment with OrderID: {$orderId}");
        $res = $payment->whereOrderId($orderId)->firstOrFail();
        Log::info(__METHOD__ . ". Find Contract with ID: {$res->contract_id}");
        $contract = Contracts::with('company')->whereId($res->contract_id)->whereStatus(
            Contracts::STATUS_DRAFT
        )->firstOrFail();
        Log::info(__METHOD__ . ". Start check payment status with OrderID: {$orderId}");
        $status = $this->payService->getOrderStatus($orderId);
        Log::info(__METHOD__ . ". Status: {$status['status']}");
        if (isset($status['isPayed']) && $status['isPayed']) {
            return $this->successResponse($this->driverService->acceptPayment($contract));
        } else {
            return $this->errorResponse(500, [], [], 'Оплата заказа не обработана. Статус: ' . $status["status"]);
        }
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
    public function postPolicySend(Request $request, $contractId)
    {
        Log::info(__METHOD__ . ". Find Contract with ID: {$contractId}");
        $contract = Contracts::findOrFail($contractId);
        $result = $this->driverService->sendMail($contract);

        Log::info(__METHOD__ . ". Response", [$result]);
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
    public function getPolicyPayLink(Request $request, $contractId)
    {
        Log::info(__METHOD__ . ". Find Contract with ID: {$contractId}");
        $contract = Contracts::findOrFail($contractId);
        $this->driverService->triggerGetLink($contract);
        try {
            if (!Payments::whereContractId($contract->id)->first()) {
                [
                    'invoice_num' => $invoiceNum,
                    'order_id' => $orderId,
                    'form_url' => $formUrl,
                ] = $this->driverService->getPayLink($this->payService, $contract, $request);

                $payment = Payments::whereContractId($contract->id)->firstOrCreate(
                    ['contract_id' => $contract->id],
                    ['invoice_num' => $invoiceNum, 'order_id' => $orderId]
                );
                $payment->contract()->associate($contract);
                $payment->save();
            } else {
                throw new RuntimeException('Данный заказ уже обработан', Response::HTTP_BAD_REQUEST);
            }
        } catch (Exception $e) {
            Log::error($e->getMessage() . ' (code: ' . $e->getCode() . ')');
            throw new RuntimeException($e->getMessage(), $e->getCode());
        }
        $result = ['url' => $formUrl, 'orderId' => $orderId];
        Log::info(__METHOD__ . '. Response', [$result]);

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
    public function getPolicyPdf(Request $request, $contractId)
    {
        Log::info(__METHOD__ . ". Find Contract with ID: {$contractId}");
        $isSample = filter_var($request->get('sample', false), FILTER_VALIDATE_BOOLEAN);
        $contract = Contracts::findOrFail($contractId);
        Log::info(__METHOD__ . '. Params', [$contract]);

        if (!$isSample && $contract->status !== Contracts::STATUS_CONFIRMED) {
            throw new RuntimeException(
                'Невозможно сгенерировать полис, т.к. полис в статусе "ожидание оплаты"',
                Response::HTTP_BAD_REQUEST
            );
        }

        $response = $this->driverService->printPdf($contract, $isSample);
        Log::info(__METHOD__ . '. Policy generated!');

        return $this->successResponse(['url' => $response]);
    }
}
