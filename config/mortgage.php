<?php

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
        'rensins' => RensinsDriver::class,
        'alfa_msk' => AlfaMskDriver::class,
        'sberins' => SberinsDriver::class,
    ],
    'ccMailNotification' => env('CC_MAIL_NOTIFICATION', 'valentin.lukyanov@strahovka.ru'),
    'rensins' => [
        'host' => env('SC_RENISANS_HOST', 'https://apigateway.renins.com'),
        'login' => env('SC_RENISANS_LOGIN', '8NDN04d5A7SBhjsYMcYZ07rRljoa'),
        'pass' => env('SC_RENISANS_PASS', 'w8dmTvVPHKSJAlC1h0NxQSyfzUga'),
    ],
    'alfaMsk' => [
        'auth' => [
            'username' => env('SC_ALFA_AUTH_USERNAME', 'E_PARTNER'),
            'pass' => env('SC_ALFA_AUTH_PASS', 'ALFAE313'),
            'auth_url' => env('SC_ALFA_AUTH_URL', 'https://b2b-test2.alfastrah.ru/msrv/oauth/token?'),
        ],
        'merchant' => [
            'wsdl' => env('SOAP_MERCHANT_SERVICE_WSDL', 'https://b2b-test2.alfastrah.ru/cxf/partner/MerchantServices?wsdl'),
            'login' => env('SOAP_MERCHANT_SERVICE_LOGIN', 'E_PARTNER'),
            'password' => env('SOAP_MERCHANT_SERVICE_PASSWORD', 'ALFAE313'),
            'contract_wsdl' => env('SOAP_MS_GET_CONTRACT_SIGNED_WSDL', 'https://b2b-test2.alfastrah.ru/cxf/partner/GetContractSigned?wsdl'),
            'user_profile' => env('MS_REGISTER_USER_PROFILE', 'https://www.alfastrah.ru'),
        ],
        'host' => env('SC_ALFA_HOST', 'https://b2b-test2.alfastrah.ru'),
        'agentContractId' => env('SC_ALFA_AGENT_CONTRACT_ID', '6941313'),
        'managerId' => env('SC_ALFA_AGENT_MANAGER_ID', '52540865'),
        'numberIterations' => env('METHOD_ALFA_NUMBER_ITERATIONS_GET_STATUS_CONTRACT', 5)
    ]
];
