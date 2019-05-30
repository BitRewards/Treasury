<?php
return [
    'adminEmail' => getenv('ADMIN_EMAIL'),
    'supportEmail' => getenv('SUPPORT_EMAIL'),
    'fiat' => [
        'apilayer' => [
            'access_key' => getenv('APILAYER_API_KEY')
        ]
    ]
];
