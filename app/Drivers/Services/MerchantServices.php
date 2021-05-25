<?php


namespace App\Drivers\Services;


use App\Drivers\Traits\LoggerTrait;
use App\Exceptions\Drivers\AlphaException;

class MerchantServices
{
    use LoggerTrait;


    private $soap_xmlns_wsse = "http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd";
    private $soap_xmlns_wsu = "http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd";
    private $soap_wsse_password = "http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordDigest";
    private $soapMerchantServicesLogin = "E_PARTNER";
    private $soap_wsse_nonce = "http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary";

    /** @var string */
    protected $idToken;
    /** @var string */
    protected $nonceXML;
    /** @var string */
    protected $timestamp;
    /** @var string */
    protected $passDigest;

    protected array $data = [];

    public function getUpid()
    {
        $options = array(
            'soap_version' => SOAP_1_1,
            'exceptions' => true,
            'trace' => 1,
            'cache_wsdl' => WSDL_CACHE_NONE
        );
        $soap = new \SoapClient('https://b2b-test2.alfastrah.ru/cxf/partner/PartnersInteraction?wsdl', $options);

        try {
            $result = $soap->__SoapCall('getUPID', [
                'UPIDRequest' => [
                    'callerCode' => 'E_PARTNER',
                ],
            ], null, $this->getHeaderForSoap());

            $resp = $soap->__getLastRequestHeaders();
        } catch (\Throwable $e) {
            self::abortLog($e->getMessage(), AlphaException::class);
        }

        return collect($result);
    }

    public function getContractId($orderId)
    {
        $options = array(
            'soap_version' => SOAP_1_1,
            'exceptions' => true,
            'trace' => 1,
            'cache_wsdl' => WSDL_CACHE_NONE
        );
        $soap = new \SoapClient('https://b2b-test2.alfastrah.ru/cxf/partner/PartnersInteraction?wsdl', $options);

        try {
            $result = $soap->__SoapCall('getContractId', [
                'getPayedContractRequest' => [
                    'UPID' => $orderId,
                ],
            ], null, $this->getHeaderForSoap());

            $resp = $soap->__getLastRequestHeaders();
        } catch (\Throwable $e) {
            self::abortLog($e->getMessage(), AlphaException::class);
        }

        return collect($result);
    }

    public function getContractSigned($orderId, $contractId)
    {
        $options = array(
            'soap_version' => SOAP_1_1,
            'exceptions' => true,
            'trace' => 1,
            'cache_wsdl' => WSDL_CACHE_NONE
        );
        $soap = new \SoapClient(env('SOAP_MS_GET_CONTRACT_SIGNED_WSDL'), $options);
        $files = [];
        foreach ($contractId as $id) {
            try {

                $result = $soap->__SoapCall('GetContractSigned', [
                    'GetContractSignedRequest' => [
                        'UPID' => $orderId,
                        'ContractId' => $id
                    ],
                ], null, $this->getHeaderForSoap());

                $resp = $soap->__getLastRequestHeaders();

                $filePath = 'alpha/policy/' . uniqid(time(), false) . '.pdf';
                \Storage::put($filePath, $result->Content);
                $files[] = $filePath;
            } catch (\Throwable $e) {
                self::abortLog($e->getMessage(), AlphaException::class);
            }
        }

        return $files;
    }

    /**
     * @param $orderId
     * @return \Illuminate\Support\Collection
     * @throws \SoapFault
     */
    public function getOrderStatus($orderId)
    {
        $options = array(
            'soap_version' => SOAP_1_1,
            'exceptions' => true,
            'trace' => 1,
            'cache_wsdl' => WSDL_CACHE_NONE
        );
        $soap = new \SoapClient(env('SOAP_MERCHANT_SERVICE_WSDL'), $options);

        try {
            $result = $soap->__SoapCall('getOrderStatus', [
                'order' => [
                    'orderId' => $orderId,
                ],
            ], null, $this->getHeaderForSoap());

            $resp = $soap->__getLastRequestHeaders();
        } catch (\Throwable $e) {
            self::abortLog($e->getMessage(), AlphaException::class);
        }

        return collect($result);
    }

    public function registerOrder()
    {
        $options = array(
            'soap_version' => SOAP_1_1,
            'exceptions' => true,
            'trace' => 1,
            'cache_wsdl' => WSDL_CACHE_NONE
        );
        $soap = new \SoapClient(env('SOAP_MERCHANT_SERVICE_WSDL'), $options);

        try {
            $result = $soap->__SoapCall('registerOrder', [
                'order' => [
                    'merchantOrderNumber' => $this->data['merchantOrderNumber'],
                    'description' => $this->data['description'][1],
                    'expirationDate' => $this->data['expirationDate'],
                    'isOperDocument' => 'y',
                    'returnUrl' => $this->data['returnUrl'],
                    'failUrl' => $this->data['failUrl']
                ],
            ], null, $this->getHeaderForSoap());

            $resp = $soap->__getLastRequestHeaders();
        } catch (\Throwable $e) {
            self::abortLog($e->getMessage(), AlphaException::class);
        }

        return collect($result);
    }

    protected function authParam()
    {
        $nonce = mt_rand();
        $this->idToken = md5(base64_encode(pack('H*', $nonce)));
        $this->nonceXML = base64_encode(pack('H*', $nonce));
        $timeTmp = new \DateTime('now');

        $this->timestamp = $timeTmp->format('Y-m-d\TH:i:s\Z'); // 2016-02-25T11:24:18Z date('c');
        unset($timeTmp);

        $packedNonce = pack('H*', $nonce);
        $packedTimestamp = pack('a*', $this->timestamp);
        $packedPassword = pack('a*', env('SOAP_MERCHANT_SERVICE_PASSWORD'));

        $hash = sha1($packedNonce . $packedTimestamp . $packedPassword);
        $packedHash = pack('H*', $hash);

        $this->passDigest = base64_encode($packedHash);
    }

    protected function getHeaderForSoap(): \SoapHeader
    {
        $this->authParam();

        $xml =
            <<<XML
        <SOAP-ENV:Header>
            <wsse:Security SOAP-ENV:mustUnderstand="1" xmlns:wsse="{$this->soap_xmlns_wsse}"
            xmlns:wsu="{$this->soap_xmlns_wsu}">
                <wsse:UsernameToken wsu:Id="$this->idToken" xmlns:wsu="{$this->soap_xmlns_wsu}">
                    <wsse:Username>{$this->soapMerchantServicesLogin}</wsse:Username>
                    <wsse:Password Type="{$this->soap_wsse_password}">{$this->passDigest}</wsse:Password>
                    <wsse:Nonce EncodingType="{$this->soap_wsse_nonce}">{$this->nonceXML}</wsse:Nonce>
                    <wsu:Created>{$this->timestamp}</wsu:Created>
                </wsse:UsernameToken>
            </wsse:Security>
        </SOAP-ENV:Header>
        XML;

        $xml = str_replace("\n", '', $xml);

        $headerVar = new \SoapVar($xml, XSD_ANYXML);

        return (new \SoapHeader($this->soap_xmlns_wsse, 'Security', $headerVar, true));
    }

    public function setMerchantOrderNumber(int $merchantOrderNumber)
    {
        $this->data['merchantOrderNumber'] = $merchantOrderNumber;
    }

    public function setDescription($description)
    {
        $this->data['description'] = $description;
    }

    public function setExpirationDate(string $expirationDate)
    {
        $this->data['expirationDate'] = $expirationDate;
    }

    public function setIsOperDocument(string $isOperDocument)
    {
        $this->data['isOperDocument'] = $isOperDocument;
    }

    public function setClientId(int $clientId)
    {
        $this->data['clientId'] = $clientId;
    }

    public function setReturnUrl(string $returnUrl)
    {
        $this->data['returnUrl'] = $returnUrl;
    }

    public function setFailUrl(string $failUrl)
    {
        $this->data['failUrl'] = $failUrl;
    }
}
