<?php

use App\Drivers\AlfaMskDriver;
use App\Drivers\RensinsDriver;
use App\Drivers\SberinsDriver;

return [
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
        'host' => env('SC_RENISANS_HOST'),
        'login' => env('SC_RENISANS_LOGIN'),
        'pass' => env('SC_RENISANS_PASS'),
    ],
    'alfaMsk' => [
        'auth' => [
            'username' => env('SC_ALFA_AUTH_USERNAME'),
            'pass' => env('SC_ALFA_AUTH_PASS'),
            'auth_url' => env('SC_ALFA_AUTH_URL'),
        ],
        'host' => env('SC_ALFA_HOST'),
        'agentContractId' => env('SC_ALFA_AGENT_CONTRACT_ID'),
        'managerId' => env('SC_ALFA_AGENT_MANAGER_ID'),
        'numberIterations' => env('METHOD_ALFA_NUMBER_ITERATIONS_GET_STATUS_CONTRACT')
    ]
];
