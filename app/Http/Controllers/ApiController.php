<?php

namespace App\Http\Controllers;

use App\Drivers\Traits\LoggerTrait;
use App\Http\Requests\CalculateRequest;
use App\Http\Requests\CreatePolicyRequest;
use App\Models\Contracts;
use App\Models\Payments;
use App\Services\DriverService;
use App\Services\PayService\PayLinks;
use Carbon\Carbon;
use CodeDredd\Soap\Facades\Soap;
use CodeDredd\Soap\SoapClient;
use Exception;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Ramsey\Uuid\Generator\RandomBytesGenerator;
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
        /**
         * array:2 [
         * 0 => "orderResult refundOrder(refundOrderParams $order)"
         * 1 => "createBindingNoPaymentResponse createBindingNoPayment(createBindingNoPaymentRequest $request)"
         * 2 => "orderResult unBindCard(string $bindingId)"
         * 3 => "registerOrderResponse registerOrder(orderParams $order)"
         * 4 => "orderResult addParams(addParamsRequest $request)"
         * 5 => "androidPayPaymentResponse androidPay(androidPayPaymentRequest $arg0)"
         * 6 => "googlePayResponse googlePay(googlePayRequest $arg0)"
         * 7 => "applePayPaymentResponse applePay(applePayPaymentRequest $arg0)"
         * 8 => "paymentOrderResult paymentOrderBinding(paymentOrderBindingParams $order)"
         * 9 => "getBindingsResponse getBindings(getBindingsRequest $request)"
         * 10 => "orderInfoArray getLastOrders(dateTime $from, dateTime $to)"
         * 11 => "getOrderStatusExtendedResponse getOrderStatusExtended(getOrderStatusExtendedRequest $order)"
         * 12 => "registerOrderResponse registerOrderPreAuth(orderParams $order)"
         * 13 => "extendBindingResponse extendBinding(extendBindingRequest $request)"
         * 14 => "verifyEnrollmentResponse verifyEnrollment(string $pan)"
         * 15 => "getLastOrdersForMerchantsResponse getLastOrdersForMerchants(getLastOrdersForMerchantsRequest $request)"
         * 16 => "orderResult updateSSLCardList(string $mdorder)"
         * 17 => "getBindingsResponse getBindingsByCardOrId(getBindingsByCardOrIdRequest $request)"
         * 18 => "orderResult reverseOrder(reversalOrderParams $order)"
         * 19 => "orderResult bindCard(string $bindingId)"
         * 20 => "finishThreeDSResponse finishThreeDs(finishThreeDSRequest $request)"
         * 21 => "orderResult updateBlackCardList(string $mdorder)"
         * 22 => "paymentOrderOtherWayResult paymentOrderOtherWay(paymentOrderOtherWayParams $order)"
         * 23 => "orderResult checkAuthenticate(loginParams $login)"
         * 24 => "orderResult updateWhiteCardList(string $mdorder)"
         * 25 => "orderResult depositOrder(depositOrderParams $order)"
         * 26 => "orderStatusResponse getOrderStatus(orderStatusRequest $order)"
         * 27 => "paymentOrderResult paymentOrder(paymentOrderParams $order)"
         * ]
         */

        /**
         * Request параметры для registerOrder
         *  struct orderParams {
        string returnUrl;
        string failUrl;
        string merchantLogin;
        string email;
        serviceParam params;
        string clientId;
        orderBundle orderBundle;
        features features;
        string merchantOrderNumber;
        string description;
        long amount;
        string currency;
        string language;
        string pageView;
        int sessionTimeoutSecs;
        string bindingId;
        dateTime expirationDate;
        dateTime autocompletionDate;
        YesNo accidentPolicyPermission;
        YesNo propertyPolicyPermission;
        YesNo isOperDocument;
        decimal lifeLineDonationAmount;
        string clientEmail;
        }
         */
        $password = "ALFAE313";
        $nonce = base64_encode(pack("L", rand(0,1000)));
        $created = Carbon::now()->format('Y-m-d\TH:i:s.v\Z');
        $passwordDigest = base64_encode(sha1($nonce . $created . $password, true));
        dd($passwordDigest);


        $passwordDigest2 = base64_encode(sha1($nonce . $created . $password, true));

        $options = array(
            'soap_version'=>SOAP_1_1,
            'exceptions'=>false,
            'trace'=>1,
            'cache_wsdl'=>WSDL_CACHE_NONE
        );
        $soap = new \SoapClient('https://b2b-test2.alfastrah.ru/cxf/partner/MerchantServices?wsdl',$options);

        $headerVar = new \SoapVar("<IdentityHeader>
<SOAP-ENV:Header>
<wsse:Security xmlns:wsse='http://docs.oasis-open.org/wss/2004/01/oasis-200401-
wss-wssecurity-secext-1.0.xsd' xmlns:wsu='http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd'>
 <wsse:UsernameToken wsu:Id='UsernameToken-DD4E97480F1F85448316117490736656'>
 <wsse:Username>E_PARTNER</wsse:Username>
 <wsse:Password Type='http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile1.0#PasswordDigest'>$passwordDigest</wsse:Password>
 <wsse:Nonce EncodingType='http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-messagesecurity-1.0#Base64Binary'>$nonce</wsse:Nonce>
 <wsu:Created>$created</wsu:Created>
 </wsse:UsernameToken>
 </wsse:Security>
 </SOAP-ENV:Header>
 </IdentityHeader>",
            XSD_ANYXML);
        $header = new \SoapHeader('https://b2b-test2.alfastrah.ru/cxf/partner/MerchantServices?wsdl','MerchantServiceImplService',$headerVar, true);
        $soap->__setSoapHeaders($header);

//        $reg = $soap->registerOrder();
        try{
            $result = $soap->__SoapCall('registerOrder', []);
        }catch (\Exception $e){
            throw new \Exception($soap->__getLastRequestHeaders());
        }
        dd($result, $soap);
//
//        $soap = new \SoapClient('https://www.dataaccess.com/webservicesserver/NumberConversion.wso?wsdl');
//
//        $reg = $soap->NumberToWords(['ubiNum' => 500]);
//        dd($reg);

//        ->withHeaders([
//        "<soapenv:Header>
// <wsse:Security soapenv:mustUnderstand='1' xmlns:wsse='http://docs.oasis-open.org/wss/2004/01/oasis-200401-
//wss-wssecurity-secext-1.0.xsd' xmlns:wsu='http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurityutility-1.0.xsd'>
// <wsse:UsernameToken wsu:Id='UsernameToken-DD4E97480F1F85448316117490736656'>
// <wsse:Username>E_PARTNER</wsse:Username>
// <wsse:Password Type='http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile1.0#PasswordDigest'>$password</wsse:Password>
// <wsse:Nonce EncodingType='http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-messagesecurity-1.0#Base64Binary'>$nonce</wsse:Nonce>
// <wsu:Created>$created</wsu:Created>
// </wsse:UsernameToken>
// </wsse:Security>
//</soapenv:Header>"
//    ])

//        $response = Soap::baseWsdl('https://www.dataaccess.com/webservicesserver/NumberConversion.wso?wsdl')
//        $response = Soap::baseWsdl('https://b2b-test2.alfastrah.ru/cxf/partner/MerchantServices?wsdl')
//            ->withHeaders([
//            'Header' => "<IdentityHeader><SOAP-ENV:Header><wsse:Security SOAP-ENV:mustUnderstand='1' xmlns:wsse='http://docs.oasis-open.org/wss/2004/01/oasis-200401-
//wss-wssecurity-secext-1.0.xsd' xmlns:wsu='http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd'>
// <wsse:UsernameToken wsu:Id='UsernameToken-DD4E97480F1F85448316117490736656'>
// <wsse:Username>E_PARTNER</wsse:Username>
// <wsse:Password Type='http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile1.0#PasswordDigest'>$passwordDigest</wsse:Password>
// <wsse:Nonce EncodingType='http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-messagesecurity-1.0#Base64Binary'>$nonce</wsse:Nonce>
// <wsu:Created>$created</wsu:Created>
// </wsse:UsernameToken>
// </wsse:Security></SOAP-ENV:Header></IdentityHeader>"
//        ])
//            ->registerOrder([]);
//            ->NumberToWords(['ubiNum' => 1100]);
//        dd($response->body());
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
