<?php

namespace console\controllers;

use yii\console\Controller;

class BackgroundController extends Controller
{
    private function workers()
    {
        $workers = [];

        foreach( \Yii::$app->controllerMap as $id => $params ) {
            if (preg_match('/-worker/', $id)) {
                $workers[] = $id;
            }
        }

        return $workers;
    }

    public function actionUp()
    {
        $nullDevice = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'nul' : '/dev/null';

        foreach( $this->workers() as $worker ) {
            $command = 'php ' . \Yii::getAlias('@app') . '/../yii ' . $worker . ' 1>'.$nullDevice.' 2>&1 &';
            echo $command . PHP_EOL;
            exec($command);
        }
    }

    public function actionDown()
    {
        foreach( $this->workers() as $worker ) {
            $command = "pkill --signal 9 -f 'yii {$worker}'";
            echo $command . PHP_EOL;
            exec($command);
        }
    }

    public function actionRedo()
    {
        $this->actionDown();
        $this->actionUp();
    }
}
