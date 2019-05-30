<?php
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'console\controllers',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'controllerMap' => [
        'fixture' => [
            'class' => 'yii\console\controllers\FixtureController',
            'namespace' => 'common\fixtures',
        ],
        'migrate' => [
            'class' => 'yii\console\controllers\MigrateController',
            'templateFile' => '@console/components/migration/view.php'
        ],
        'transaction-parser-worker'   => 'console\workers\beanstalk\TransactionParserWorker',
        'withdrawal-worker'   => 'console\workers\beanstalk\WithdrawalWorker',
        'callback-worker'   => 'console\workers\beanstalk\CallbackWorker',
        'block-queue-worker' => 'console\workers\beanstalk\BlockQueueWorker',
        'block-parser-worker' => 'console\workers\beanstalk\BlockParserWorker',
    ],
    'components' => [
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'mutex' => [
            'class' => 'yii\redis\Mutex',
            'redis' => [
                'hostname' => 'localhost',
                'port' => 6379,
                'database' => 0,
            ],
            'expire' => 24 * 3600
        ],
    ],
    'params' => $params,
];
