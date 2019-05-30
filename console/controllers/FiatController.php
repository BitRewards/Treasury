<?php
namespace console\controllers;

use common\services\FiatService;
use yii\console\Controller;
use yii\helpers\ArrayHelper;

class FiatController extends Controller
{
    public function actionRefreshRates()
    {
        $fiatService = new FiatService();
        array_map(function($currency) use ($fiatService) {
            $value = $fiatService->getUSDRate($currency, $forceUpdate = true);
            echo $currency . '=' . $value . PHP_EOL;
        }, ArrayHelper::merge(FiatService::$CURRENCY_LIST, FiatService::$COIN_LIST));
    }

    public function actionExchangeRate($currencyFrom, $currencyTo)
    {
        $fiat = new FiatService();

        $rate = $fiat->getExchangeRate($currencyFrom, $currencyTo, true);
        var_dump($rate);
    }
}