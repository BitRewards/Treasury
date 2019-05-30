<?php
namespace console\controllers;

use yii\console\Controller;

class BeanstalkController extends Controller
{
    public function actionTestTube($tube)
    {
        $sampleData = [
            'ts' => time(),
            'pid' => getmypid()
        ];

        $queue = \Yii::$app->queue;
        echo $queue->add($tube, $sampleData) . "\n";
    }
}