<?php

return [
    'pdf' => [
        'path' => env('PDF_PATH', 'ns/pdf/'),
        'tmp' => env('PDF_TMP_DIR', 'tmp/')
    ],
    'ccMailNotification' => env('CC_MAIL_NOTIFICATION', 'valentin.lukyanov@strahovka.ru'),
    'rensins' => [
        'host' => env('SC_RENISANS_HOST'),
        'login' => env('SC_RENISANS_LOGIN'),
        'pass' => env('SC_RENISANS_PASS'),
    ],
    'alfaMsk' => [
        'host' => env('SC_ALFA_HOST'),
        'agentContractId' => env('SC_ALFA_AGENT_CONTRACT_ID'),
        'managerId' => env('SC_ALFA_AGENT_MANAGER_ID'),
    ]
];
