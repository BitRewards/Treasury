<?php
namespace common\services\Fiat\Tickers\Currency;

use common\models\TickerData;
use common\services\Fiat\Tickers\AbstractTicker;
use yii\helpers\Json;

class ApiLayerService extends AbstractTicker
{
    const DEST_CURRENCY = 'USD';
    const TICKER_ETHEREUM = 1027;

    public static $TICKERS = [
        'ETH' => self::TICKER_ETHEREUM
    ];

    public $baseUrl = 'http://www.apilayer.net/api/live';
    public $access_key;


    public function __construct()
    {
        $this->access_key = \Yii::$app->params['fiat']['apilayer']['access_key'] ?? null;
        parent::__construct();
    }

    public function getTickerId($coin)
    {
        return self::$TICKERS[$coin] ?? null;
    }

    public function requestTickerData($currency)
    {
        return $this->queryApi('?access_key='.$this->access_key.'&format=1&currencies=' . $currency);
    }

    /**
     * @param $response
     * @param $currency
     * @return object
     * @throws \yii\base\InvalidConfigException
     */
    public function convertResponse($response, $currency) : TickerData
    {
        $response = Json::decode($response);
        return new TickerData([
            'src' => $response['source'],
            'price' => $response['quotes'][self::DEST_CURRENCY . $currency],
            'dst' => $currency
        ]);
    }
}