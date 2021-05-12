<?php


namespace App\Drivers\Services;


use Carbon\Carbon;

class MerchantServices
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

    /** @var string */
    protected $nonce;
    /** @var string */
    protected $idToken;
    /** @var string */
    protected $nonceXML;
    /** @var string */
    protected $timeTmp;
    /** @var string */
    protected $timestamp;
    /** @var string */
    protected $passDigest;

    protected array $data = [];

    const PASSWORD = "ALFAE313";
    const LOGIN = 'E_PARTNER';
    const WSDL_URL = 'https://b2b-test2.alfastrah.ru/cxf/partner/MerchantServices?wsdl';

    public function registerOrder()
    {

        $options = array(
            'soap_version' => SOAP_1_1,
            'exceptions'   => false,
            'trace'        => 1,
            'cache_wsdl'   => WSDL_CACHE_NONE
        );
        $soap = new \SoapClient(self::WSDL_URL, $options);

        try {
            $result = $soap->__SoapCall('registerOrder', $this->data, null, $this->getHeaderForSoap());
            $resp = $soap->__getLastRequestHeaders();
        } catch (\Exception $e) {
            throw new \Exception($soap->__getLastRequestHeaders());
        }

        return $result;
    }

    protected function getAuthParam()
    {
        $this->nonce = mt_rand();
        $this->idToken = md5(base64_encode(pack('H*', $this->nonce)));
        $this->nonceXML = base64_encode(pack('H*', $this->nonce));
        $this->timeTmp = new \DateTime('now');

        $this->timestamp = $this->timeTmp->format('Y-m-d\TH:i:s\Z'); // 2016-02-25T11:24:18Z date('c');
        unset($timeTmp);

        $packedNonce = pack('H*', $this->nonce);
        $packedTimestamp = pack('a*', $this->timestamp);
        $packedPassword = pack('a*', self::PASSWORD);

        $hash = sha1($packedNonce . $packedTimestamp . $packedPassword);
        $packedHash = pack('H*', $hash);

        $this->passDigest = base64_encode($packedHash);
    }

    protected function getHeaderForSoap(): \SoapHeader
    {
        $this->getAuthParam();

        $xml =
            <<<XML
        <SOAP-ENV:Header>
            <wsse:Security SOAP-ENV:mustUnderstand="1" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd"
            xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
                <wsse:UsernameToken wsu:Id="$this->idToken" xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
                    <wsse:Username>{self::LOGIN}</wsse:Username>
                    <wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordDigest">$this->passDigest</wsse:Password>
                    <wsse:Nonce EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary">$this->nonceXML</wsse:Nonce>
                    <wsu:Created>$this->timestamp</wsu:Created>
                </wsse:UsernameToken>
            </wsse:Security>
        </SOAP-ENV:Header>
        XML;
        $xml = str_replace("\n", '', $xml);

        $headerVar = new \SoapVar($xml, XSD_ANYXML);

        return (new \SoapHeader('http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd', 'Security', $headerVar, true));
    }

    public function setMerchantOrderNumber(int $merchantOrderNumber)
    {
        $this->data[] = [
            'merchantOrderNumber' => $merchantOrderNumber
        ];
    }

    public function setDescription($description)
    {
        $this->data[] = [
            'description' => $description
        ];
    }

    public function setExpirationDate(string $expirationDate)
    {
        $this->data[] = [
            'expirationDate' => $expirationDate
        ];
    }

    public function setIsOperDocument(string $isOperDocument)
    {
        $this->data[] = [
            'isOperDocument' => $isOperDocument
        ];
    }

    public function setClientId(int $clientId)
    {
        $this->data[] = [
            'clientId' => $clientId
        ];
    }

    public function setReturnUrl(string $returnUrl)
    {
        $this->data[] = [
            'returnUrl' => $returnUrl
        ];
    }

    public function setFailUrl(string $failUrl)
    {
        $this->data[] = [
            'failUrl' => $failUrl
        ];
    }
}
