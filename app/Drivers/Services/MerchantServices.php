<?php


namespace App\Drivers\Services;


use App\Exceptions\Drivers\AlphaException;
use Carbon\Carbon;

class MerchantServices
{
    const SOAP_XMLNS_WSSE="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd";
    const SOAP_XMLNS_WSU="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd";
    const SOAP_WSSE_PASSWORD="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordDigest";
    const SOAP_WSSE_NONCE="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary";

    /** @var string */
    protected $idToken;
    /** @var string */
    protected $nonceXML;
    /** @var string */
    protected $timestamp;
    /** @var string */
    protected $passDigest;

    protected array $data = [];

    public function registerOrder()
    {

        $options = array(
            'soap_version' => SOAP_1_1,
            'exceptions'   => false,
            'trace'        => 1,
            'cache_wsdl'   => WSDL_CACHE_NONE
        );
        $soap = new \SoapClient(env('SOAP_MERCHANT_SERVICE_WSDL'), $options);

        try {
            $result = $soap->__SoapCall('registerOrder', $this->data, null, $this->getHeaderForSoap());
            $resp = $soap->__getLastRequestHeaders();
        } catch (\Exception $e) {
            throw new AlphaException($soap->__getLastRequestHeaders());
        }

        return $result;
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
            <wsse:Security SOAP-ENV:mustUnderstand="1" xmlns:wsse={self::SOAP_XMLNS_WSSE}
            xmlns:wsu={self::SOAP_XMLNS_WSU}>
                <wsse:UsernameToken wsu:Id="$this->idToken" xmlns:wsu={self::SOAP_XMLNS_WSU}>
                    <wsse:Username>{env('SOAP_MERCHANT_SERVICE_LOGIN')}</wsse:Username>
                    <wsse:Password Type={self::SOAP_WSSE_PASSWORD}>{$this->passDigest}</wsse:Password>
                    <wsse:Nonce EncodingType={self::SOAP_WSSE_NONCE}>{$this->nonceXML}</wsse:Nonce>
                    <wsu:Created>{$this->timestamp}</wsu:Created>
                </wsse:UsernameToken>
            </wsse:Security>
        </SOAP-ENV:Header>
        XML;
        $xml = str_replace("\n", '', $xml);

        $headerVar = new \SoapVar($xml, XSD_ANYXML);

        return (new \SoapHeader(self::SOAP_XMLNS_WSSE, 'Security', $headerVar, true));
    }

    public function setMerchantOrderNumber(int $merchantOrderNumber)
    {
        $this->data['merchantOrderNumber'] =  $merchantOrderNumber;
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
