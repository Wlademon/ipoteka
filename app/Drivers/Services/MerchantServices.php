<?php


namespace App\Drivers\Services;


use App\Exceptions\Drivers\AlphaException;
use Illuminate\Support\Collection;
use SoapClient;
use SoapHeader;
use SoapVar;
use Throwable;

class MerchantServices
{

    const PARTNERS_INTERACTION = '/cxf/partner/PartnersInteraction?wsdl';


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

    /** @var string */
    protected string $host;

    protected array $data = [];

    /**
     * MerchantServices constructor.
     * @param string $host
     */
    public function __construct(string $host)
    {
        $this->host = $host;
    }

    /**
     * @return Collection
     * @throws \SoapFault
     */
    public function getUpid(): Collection
    {
        $options = array(
            'soap_version' => SOAP_1_1,
            'exceptions' => true,
            'trace' => 1,
            'cache_wsdl' => WSDL_CACHE_NONE
        );
        $soap = new SoapClient($this->host . self::PARTNERS_INTERACTION, $options);

        try {
            $result = $soap->__SoapCall('getUPID', [
                'UPIDRequest' => [
                    'callerCode' => 'E_PARTNER',
                ],
            ], null, $this->getHeaderForSoap());

            $resp = $soap->__getLastRequestHeaders();
        } catch (Throwable $e) {
            throw new AlphaException($e->getMessage());
        }

        return collect($result);
    }

    /**
     * @param $orderId
     * @return \Illuminate\Support\Collection
     * @throws \SoapFault
     */
    public function getContractId($orderId): Collection
    {
        $options = array(
            'soap_version' => SOAP_1_1,
            'exceptions' => true,
            'trace' => 1,
            'cache_wsdl' => WSDL_CACHE_NONE
        );
        $soap = new SoapClient(
            $this->host . self::PARTNERS_INTERACTION,
            $options
        );

        try {
            $result = $soap->__SoapCall('getContractId', [
                'getPayedContractRequest' => [
                    'UPID' => $orderId,
                ],
            ], null, $this->getHeaderForSoap());

            $resp = $soap->__getLastRequestHeaders();
        } catch (Throwable $e) {
            throw new AlphaException($e->getMessage());
        }

        return collect($result);
    }

    /**
     * @param $orderId
     * @param $contractId
     * @return array
     * @throws \SoapFault
     */
    public function getContractSigned($orderId, $contractId): array
    {
        $options = array(
            'soap_version' => SOAP_1_1,
            'exceptions' => true,
            'trace' => 1,
            'cache_wsdl' => WSDL_CACHE_NONE
        );
        $soap = new SoapClient(config('mortgage.alfa_msk.merchant.contract_wsdl'), $options);
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
                $files[$id] = $filePath;
            } catch (Throwable $e) {
                throw new AlphaException($e->getMessage());
            }
        }

        return $files;
    }

    /**
     * @param $orderId
     * @return \Illuminate\Support\Collection
     * @throws \SoapFault
     */
    public function getOrderStatus($orderId): Collection
    {
        $options = array(
            'soap_version' => SOAP_1_1,
            'exceptions' => true,
            'trace' => 1,
            'cache_wsdl' => WSDL_CACHE_NONE
        );
        $soap = new SoapClient(config('mortgage.alfa_msk.merchant.wsdl'), $options);

        try {
            $result = $soap->__SoapCall('getOrderStatus', [
                'order' => [
                    'orderId' => $orderId,
                ],
            ], null, $this->getHeaderForSoap());

            $resp = $soap->__getLastRequestHeaders();
        } catch (Throwable $e) {
            throw new AlphaException($e->getMessage());
        }

        return collect($result);
    }

    /**
     * @return Collection
     * @throws \SoapFault
     */
    public function registerOrder(): Collection
    {
        $options = array(
            'soap_version' => SOAP_1_1,
            'exceptions' => true,
            'trace' => 1,
            'cache_wsdl' => WSDL_CACHE_NONE
        );
        $soap = new SoapClient(config('mortgage.alfa_msk.merchant.wsdl'), $options);

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
        } catch (Throwable $e) {
            throw new AlphaException($e->getMessage());
        }

        return collect($result);
    }


    protected function authParam(): void
    {
        $nonce = mt_rand();
        $this->idToken = md5(base64_encode(pack('H*', $nonce)));
        $this->nonceXML = base64_encode(pack('H*', $nonce));
        $timeTmp = new \DateTime('now');

        $this->timestamp = $timeTmp->format('Y-m-d\TH:i:s\Z'); // 2016-02-25T11:24:18Z date('c');
        unset($timeTmp);

        $packedNonce = pack('H*', $nonce);
        $packedTimestamp = pack('a*', $this->timestamp);
        $packedPassword = pack('a*', config('mortgage.alfa_msk.merchant.password'));

        $hash = sha1($packedNonce . $packedTimestamp . $packedPassword);
        $packedHash = pack('H*', $hash);

        $this->passDigest = base64_encode($packedHash);
    }

    /**
     * @return SoapHeader
     */
    protected function getHeaderForSoap(): SoapHeader
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

        $headerVar = new SoapVar($xml, XSD_ANYXML);

        return (new SoapHeader($this->soap_xmlns_wsse, 'Security', $headerVar, true));
    }

    /**
     * @param int $merchantOrderNumber
     */
    public function setMerchantOrderNumber(int $merchantOrderNumber): void
    {
        $this->data['merchantOrderNumber'] = $merchantOrderNumber;
    }

    /**
     * @param $description
     */
    public function setDescription(string $description): void
    {
        $this->data['description'] = $description;
    }

    /**
     * @param string $expirationDate
     */
    public function setExpirationDate(string $expirationDate): void
    {
        $this->data['expirationDate'] = $expirationDate;
    }

    /**
     * @param string $isOperDocument
     */
    public function setIsOperDocument(string $isOperDocument): void
    {
        $this->data['isOperDocument'] = $isOperDocument;
    }

    /**
     * @param int $clientId
     */
    public function setClientId(int $clientId): void
    {
        $this->data['clientId'] = $clientId;
    }

    /**
     * @param string $returnUrl
     */
    public function setReturnUrl(string $returnUrl): void
    {
        $this->data['returnUrl'] = $returnUrl;
    }

    /**
     * @param string $failUrl
     */
    public function setFailUrl(string $failUrl): void
    {
        $this->data['failUrl'] = $failUrl;
    }
}
