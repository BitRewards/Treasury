<?php

/**
 * Yii bootstrap file.
 * Used for enhanced IDE code autocompletion.
 */
class Yii extends \yii\BaseYii
{
    /**
     * @var BaseApplication the application instance
     */
    public static $app;
}

/**
 * Class BaseApplication
 * @property \common\services\GiftdService $giftd
 * @property \common\services\EthService $eth
 * @property \common\services\providers\QueueProvider $queue
 * @property \common\services\EtherscanService $etherscan
 */
abstract class BaseApplication extends yii\base\Application
{
}
