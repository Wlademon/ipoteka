<?php

use App\Drivers\AbsoluteDriver;
use App\Drivers\AlfaMskDriver;
use App\Drivers\RensinsDriver;
use App\Drivers\SberinsDriver;

return [
    'str_host' => env('STR_HOST', 'https://strahovka.ru'),
    'pdf' => [
        'path' => env('PDF_PATH', 'ns/pdf/'),
        'tmp' => env('PDF_TMP_DIR', 'tmp/')
    ],
    'drivers' => [
        RensinsDriver::code() => RensinsDriver::class,
        AlfaMskDriver::code() => AlfaMskDriver::class,
        SberinsDriver::code() => SberinsDriver::class,
        AbsoluteDriver::code() => AbsoluteDriver::class,
    ],
    'ccMailNotification' => env('CC_MAIL_NOTIFICATION', 'valentin.lukyanov@strahovka.ru'),
    'rensins' => [
        'host' => env('SC_RENISANS_HOST', 'https://apigateway.renins.com'),
        'login' => env('SC_RENISANS_LOGIN', '8NDN04d5A7SBhjsYMcYZ07rRljoa'),
        'pass' => env('SC_RENISANS_PASS', 'w8dmTvVPHKSJAlC1h0NxQSyfzUga'),
        'actions' => [
            'URL_CALCULATE' => env('SC_RENISANS_URL_CALCULATE', '/IpotekaAPI/1.0.0/calculate'),
            'URL_PRINT' => env('SC_RENISANS_URL_PRINT', '/IpotekaAPI/1.0.0/print'),
            'URL_SAVE' => env('SC_RENISANS_URL_SAVE', '/IpotekaAPI/1.0.0/save'),
            'URL_PAY' => env('SC_RENISANS_URL_PAY', '/IpotekaAPI/1.0.0/getPaymentLink'),
            'URL_ISSUE' => env('SC_RENISANS_URL_ISSUE','/IpotekaAPI/1.0.0/issue'),
            'URL_STATUS' => env('SC_RENISANS_URL_STATUS', '/IpotekaAPI/1.0.0/getIssueProcessStatus'),
            'URL_IMPORT' => env('SC_RENISANS_URL_IMPORT', '/IpotekaAPI/1.0.0/import'),
        ],
        'temp_path' => 'temp/'
    ],
    'alfa_msk' => [
        'auth' => [
            'username' => env('SC_ALFA_AUTH_USERNAME', 'E_PARTNER'),
            'pass' => env('SC_ALFA_AUTH_PASS', 'ALFAE313'),
            'auth_url' => env('SC_ALFA_AUTH_URL', 'https://b2b-test2.alfastrah.ru/msrv_dev/oauth/token?'),
        ],
        'merchant' => [
            'wsdl' => env('SOAP_MERCHANT_SERVICE_WSDL', 'https://b2b-test2.alfastrah.ru/cxf/partner/MerchantServices?wsdl'),
            'login' => env('SOAP_MERCHANT_SERVICE_LOGIN', 'E_PARTNER'),
            'password' => env('SOAP_MERCHANT_SERVICE_PASSWORD', 'ALFAE313'),
            'contract_wsdl' => env('SOAP_MS_GET_CONTRACT_SIGNED_WSDL', 'https://b2b-test2.alfastrah.ru/cxf/partner/GetContractSigned?wsdl'),
            'user_profile' => env('MS_REGISTER_USER_PROFILE', 'https://www.alfastrah.ru'),
        ],
        'actions' => [
            'POST_POLICY_URL' => env('SC_ALFA_POST_POLICY_URL', '/mortgage/partner/calc'),
            'POST_POLICY_CREATE_URL' => env('SC_ALFA_POST_POLICY_CREATE_URL', '/mortgage/partner/calcAndSave'),
            'GET_POLICY_STATUS_URL' => env('SC_ALFA_GET_POLICY_STATUS_URL', '/mortgage/partner/contractStatus'),
            'POST_PAYMENT_RECEIPT' => env('SC_ALFA_POST_PAYMENT_RECEIPT', '/payment/receipt/common'),
        ],
        'host' => env('SC_ALFA_HOST', 'https://b2b-test2.alfastrah.ru/msrv_dev'),
        'merchan_host' => env('SC_ALFA_HOST_MERCHANT', 'https://b2b-test2.alfastrah.ru'),
        'agentContractId' => env('SC_ALFA_AGENT_CONTRACT_ID', '6941313'),
        'managerId' => env('SC_ALFA_AGENT_MANAGER_ID', '52540865'),
        'numberIterations' => env('METHOD_ALFA_NUMBER_ITERATIONS_GET_STATUS_CONTRACT', 5)
    ],
    'absolut_77' => [
        'pay_host' => env('MERCURIUS_HOST', 'http://mercurius.stage.strahovka.ru/'),
        'actions' => [
            'calculate_life_path' => env('SC_ABSOLUT_CALCULATE_LIFE_PATH', '/api/mortgage/sber/life/calculation/create'),
            'calculate_property_path' => env('SC_ABSOLUT_CALCULATE_PROPERTY_PATH', '/api/mortgage/sber/property/calculation/create'),
            'life_agreement_path' => env('SC_ABSOLUT_LIFE_AGREEMENT_PATH', '/api/mortgage/sber/life/agreement/create'),
            'property_agreement_path' => env('SC_ABSOLUT_PROPERTY_AGREEMENT_PATH', '/api/mortgage/sber/property/agreement/create'),
            'print_policy_path' => env('SC_ABSOLUT_PRINT_POLICY_PATH', '/api/print/agreement/'),
            'released_policy_path' => env('SC_ABSOLUT_RELEASED_POLICY_PATH', '/api/agreement/set/released/'),
            'auth_path' => env('SC_ABSOLUT_AUTH_PATH', '/oauth/token'),
        ],
        'base_Url' =>env('SC_ABSOLUT_HOST','https://represtapi.absolutins.ru/ords/rest'),
        'client_id'=>env('SC_ABSOLUT_CLIENT_ID','Wpsa0QvBoyjwUMQYJ6707A..'),
        'client_secret'=>env('SC_ABSOLUT_CLIENT_SECRET','waSVo19oyiyd78T-QCMxIw..'),
        'grant_type'=>env('SC_ABSOLUT_GRANT_TYPE','client_credentials'),
        'pdf' => [
            'path' => env('PDF_PATH', 'ab/pdf/'),
        ],
    ],
    'mercuriusHost' => env('MERCURIUS_HOST', 'http://mercurius.stage.strahovka.ru/'),
];
