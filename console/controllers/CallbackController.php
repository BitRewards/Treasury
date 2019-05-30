<?php

namespace console\controllers;

use common\models\Callback;
use Prophecy\Call\Call;
use yii\console\Controller;

class CallbackController extends Controller
{
    public function actionQueue()
    {
        $query = Callback::find()
            ->where(['or', ['status' => Callback::STATUS_NOT_PROCESSED], [
                'and',
                ['status' => [Callback::STATUS_INITIATED, Callback::STATUS_WAITING_RETRY]],
                ['<', 'updated_at', time() - Callback::REPLY_INTERVAL]
            ]]);

        foreach ($query->batch() as $callbackBatch) {
            foreach ($callbackBatch as $callback) {
                \Yii::$app->queue->add('callback', $callback->attributes);
            }
        }
    }

    public function actionEndpoint()
    {
        $uri = \Yii::$app->giftd->getBalanceNotificationEndpoint();

        var_dump($uri->__toString());
    }
}