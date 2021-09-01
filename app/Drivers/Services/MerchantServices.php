<?php

namespace App\Drivers\Services;

use App\Exceptions\Drivers\AlphaException;
use DateTime;
use Illuminate\Support\Collection;
use Log;
use SoapClient;
use SoapHeader;
use SoapVar;
use Storage;
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
     *
     * @param  string  $host
     */
    public function __construct(string $host)
    {
        $this->host = $host;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return Collection
     * @throws \SoapFault
     */
    public function getUpid(): Collection
    {
        $options = [
            'soap_version' => SOAP_1_1,
            'exceptions' => true,
            'trace' => 1,
            'cache_wsdl' => WSDL_CACHE_NONE,
        ];
        $soap = new SoapClient($this->host . self::PARTNERS_INTERACTION, $options);

        try {
            $request = [
                'UPIDRequest' => [
                    'callerCode' => 'E_PARTNER',
                ],
            ];
            $header = $this->getHeaderForSoap();
            Log::info(
                __METHOD__ . ' Получение UPID',
                [
                    'request' => $request,
                    'secure' => $header,
                ]
            );
            $result = $soap->__SoapCall('getUPID', $request, null, $header);
            Log::info(
                __METHOD__ . ' UPID получен',
                [
                    'request' => $soap->__getLastRequest(),
                    'response' => $soap->__getLastResponse(),
                    'headers' => $soap->__getLastResponseHeaders(),
                ]
            );
        } catch (Throwable $e) {
            throw new AlphaException($e->getMessage());
        }

        return collect($result);
    }

    /**
     * @param $orderId
     *
     * @return \Illuminate\Support\Collection
     * @throws \SoapFault
     */
    public function getContractId(string $orderId): Collection
    {
        $options = [
            'soap_version' => SOAP_1_1,
            'exceptions' => true,
            'trace' => 1,
            'cache_wsdl' => WSDL_CACHE_NONE,
        ];
        $soap = new SoapClient(
            $this->host . self::PARTNERS_INTERACTION, $options
        );

        try {
            $request = [
                'getPayedContractRequest' => [
                    'UPID' => $orderId,
                ],
            ];
            $header = $this->getHeaderForSoap();
            Log::info(
                __METHOD__ . ' Получение идентификатора сделки',
                [
                    'request' => $request,
                    'secure' => $header,
                ]
            );
            $result = $soap->__soapCall('getContractId', $request, null, $header);

            Log::info(
                __METHOD__ . ' Идентификатор сделки получен',
                [
                    'request' => $soap->__getLastRequest(),
                    'response' => $soap->__getLastResponse(),
                    'headers' => $soap->__getLastResponseHeaders(),
                ]
            );
        } catch (Throwable $e) {
            throw new AlphaException($e->getMessage());
        }

        return collect($result);
    }

    /**
     * @param $orderId
     * @param $contractId
     *
     * @return array
     * @throws \SoapFault
     */
    public function getContractSigned(string $orderId, array $contractId): array
    {
        $options = [
            'soap_version' => SOAP_1_1,
            'exceptions' => true,
            'trace' => 1,
            'cache_wsdl' => WSDL_CACHE_NONE,
        ];
        $soap = new SoapClient(config('mortgage.alfa_msk.merchant.contract_wsdl'), $options);
        $files = [];
        foreach ($contractId as $id) {
            try {
                $request = [
                    'UPID' => $orderId,
                    'ContractId' => $id,
                ];
                $header = $this->getHeaderForSoap();
                Log::info(
                    __METHOD__ . ' Получение полисов',
                    [
                        'request' => $request,
                        'secure' => $header,
                    ]
                );
                $result = $soap->__soapCall(
                    'GetContractSigned',
                    [
                        'GetContractSignedRequest' => $request,
                    ],
                    null,
                    $header
                );

                Log::info(
                    __METHOD__ . ' Полисы получены',
                    [
                        'request' => $soap->__getLastRequest(),
                        'response' => $soap->__getLastResponse(),
                        'headers' => $soap->__getLastResponseHeaders(),
                    ]
                );

                $filePath = 'alpha/policy/' . uniqid(time(), false) . '.pdf';
                Storage::put($filePath, $result->Content);
                $files[$id] = $filePath;
            } catch (Throwable $e) {
                throw new AlphaException($e->getMessage());
            }
        }

        return $files;
    }

    /**
     * @param $orderId
     *
     * @return \Illuminate\Support\Collection
     * @throws \SoapFault
     */
    public function getOrderStatus($orderId): Collection
    {
        $options = [
            'soap_version' => SOAP_1_1,
            'exceptions' => true,
            'trace' => 1,
            'cache_wsdl' => WSDL_CACHE_NONE,
        ];
        $soap = new SoapClient(config('mortgage.alfa_msk.merchant.wsdl'), $options);

        try {
            $request = [
                'order' => [
                    'orderId' => $orderId,
                ],
            ];
            $header = $this->getHeaderForSoap();
            Log::info(
                __METHOD__ . ' Получение статуса',
                [
                    'request' => $request,
                    'secure' => $header,
                ]
            );
            $result = $soap->__soapCall('getOrderStatus', $request, null, $header);
            Log::info(
                __METHOD__ . ' Получение статуса',
                [
                    'request' => $soap->__getLastRequest(),
                    'response' => $soap->__getLastResponse(),
                    'headers' => $soap->__getLastResponseHeaders(),
                ]
            );
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
        $options = [
            'soap_version' => SOAP_1_1,
            'exceptions' => true,
            'trace' => 1,
            'cache_wsdl' => WSDL_CACHE_NONE,
        ];
        $soap = new SoapClient(config('mortgage.alfa_msk.merchant.wsdl'), $options);

        try {
            $request = [
                'order' => [
                    'merchantOrderNumber' => $this->data['merchantOrderNumber'],
                    'description' => $this->data['description'][1],
                    'expirationDate' => $this->data['expirationDate'],
                    'isOperDocument' => 'y',
                    'returnUrl' => $this->data['returnUrl'],
                    'failUrl' => $this->data['failUrl'],
                ],
            ];
            Log::info(
                __METHOD__ . ' Получение ссылки на оплату: регистрация заказа',
                [
                    'request' => $request,
                ]
            );
            $result = $soap->__soapCall('registerOrder', $request, null, $this->getHeaderForSoap());
            Log::info(
                __METHOD__ . ' Получение ссылки на оплату: заказ зарегистрирован',
                [
                    'request' => $soap->__getLastRequest(),
                    'response' => $soap->__getLastResponse(),
                    'headers' => $soap->__getLastResponseHeaders(),
                ]
            );

            $resp = $soap->__getLastRequestHeaders();
        } catch (Throwable $e) {
            Log::info(
                __METHOD__ . ' Получение ссылки на оплату: ошибка',
                [
                    'request' => $soap->__getLastRequest(),
                    'response' => $soap->__getLastResponse(),
                    'headers' => $soap->__getLastResponseHeaders(),
                ]
            );
            throw new AlphaException($e->getMessage(), $e->getCode(), $e);
        }

        return collect($result);
    }

    protected function authParam(): void
    {
        $nonce = mt_rand();
        $this->idToken = md5(base64_encode(pack('H*', $nonce)));
        $this->nonceXML = base64_encode(pack('H*', $nonce));
        $timeTmp = new DateTime('now');

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

        $xml = <<<XML
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
     * @param  int  $merchantOrderNumber
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
     * @param  string  $expirationDate
     */
    public function setExpirationDate(string $expirationDate): void
    {
        $this->data['expirationDate'] = $expirationDate;
    }

    /**
     * @param  string  $isOperDocument
     */
    public function setIsOperDocument(string $isOperDocument): void
    {
        $this->data['isOperDocument'] = $isOperDocument;
    }

    /**
     * @param  int  $clientId
     */
    public function setClientId(int $clientId): void
    {
        $this->data['clientId'] = $clientId;
    }

    /**
     * @param  string  $returnUrl
     */
    public function setReturnUrl(string $returnUrl): void
    {
        $this->data['returnUrl'] = $returnUrl;
    }

    /**
     * @param  string  $failUrl
     */
    public function setFailUrl(string $failUrl): void
    {
        $this->data['failUrl'] = $failUrl;
    }
}
