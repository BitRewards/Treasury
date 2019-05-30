<?php

namespace api\modules\v1\controllers;

use common\services\FiatService;
use yii\web\Controller;

class CurrencyController extends Controller
{
    /**
     * @param $currencyFrom
     * @param string $currencyTo
     * @param $amountFrom
     * @return float|int
     */
    public function actionConvert($currencyFrom, $currencyTo = 'BIT', $amountFrom)
    {
        return (new FiatService())->getExchangeRate($currencyFrom, $currencyTo) * $amountFrom;
    }
}
