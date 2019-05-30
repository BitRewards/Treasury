<?php
return [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'pgsql:host=treasury-db;dbname=treasury',
            'username' => getenv('TREASURY_DB_USER'),
            'password' => getenv('TREASURY_DB_PASSWORD'),
            'charset' => 'utf8',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'viewPath' => '@common/mail',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            'useFileTransport' => true,
        ],

        'giftd' => [
            'api_base_url' => 'https://crm.local/api/' // must be defined in local config
        ],

        'etherscan' => [
            'api_key' => getenv('ETHERSCAN_API_KEY')
        ]
    ],
];
