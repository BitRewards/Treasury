<?php
return [
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'language' => 'ru-RU',
    'sourceLanguage' => 'en-US',
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'i18n' => [
            'translations' => [
                'app' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@root/messages',
                ],
                'udokmeci.beanstalkd' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@root/messages',
                ],
            ],
        ],

        'beanstalk' => [
            'class' => 'udokmeci\yii2beanstalk\Beanstalk',
            'host' => 'treasury-beanstalkd',
        ],

        'queue' => [
            'class' => 'common\services\QueueService',
            'provider' => [
                'class' => 'common\services\providers\BeanstalkProvider',
                'beanstalk' => 'beanstalk',
            ]
        ],

        'giftd' => [
            'class' => 'common\services\GiftdService',
            'api_base_url' => '' // must be defined in local config
        ],

        'eth' => [
            'class' => 'common\services\EthService',
            'node' => '', // should be defined in local config,
            'debug' => true
        ],

        'etherscan' => [
            'class' => 'common\services\EtherscanService',
            'base_uri' => 'http://api-ropsten.etherscan.io/',
            'api_key' => '' // define in local config
        ],

        'log' => [
            'flushInterval' => 1,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'logFile' => '@runtime/logs/blockchain.log',
                    'maxFileSize' => 1024 * 1024, // 1Gb
                    'exportInterval' => 1,
                    'logVars' => [],
                    'levels' => ['error', 'warning'],
                    'categories' => [
                        'common\exceptions\InvalidBlockchainResponse',
                        'common\exceptions\NotEnoughBalanceException',
                    ],
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'maxFileSize' => 1024 * 1024, // 1Gb
                    'exportInterval' => 1,
                    'levels' => ['error', 'warning'],
                    'except' => [
                        'common\exceptions\InvalidBlockchainResponse',
                        'common\exceptions\NotEnoughBalanceException',
                    ],
                ],

                [
                    'class' => 'notamedia\sentry\SentryTarget',
                    'dsn' => '', // define that in local config
                    'exportInterval' => 1,
                    'levels' => ['error', 'warning'],
                    'context' => true // Write the context information. The default is true.
                ],

            ],
        ],
    ],
];
