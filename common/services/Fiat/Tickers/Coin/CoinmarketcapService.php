<?php
namespace common\services\Fiat\Tickers\Coin;

use common\models\TickerData;
use common\services\Fiat\Tickers\AbstractTicker;
use yii\helpers\Json;

class CoinmarketcapService extends AbstractTicker
{
    const DEST_CURRENCY = 'USD';
    const TICKER_ETHEREUM = 1027;
    const TICKER_BITCOIN = 1;

    public static $TICKERS = [
        'ETH' => self::TICKER_ETHEREUM,
        'BTC' => self::TICKER_BITCOIN,
    ];

    public $baseUrl = 'https://api.coinmarketcap.com/';


    public function getTickerId($coin)
    {
        return self::$TICKERS[$coin] ?? null;
    }

    public function requestTickerData($coin)
    {
        return $this->queryApi('v2/ticker/' . $this->getTickerId($coin) . '/?convert=' . self::DEST_CURRENCY);
    }

    /**
     * @param $response
     * @param $coin
     * @return object
     * @throws \yii\base\InvalidConfigException
     */
    public function convertResponse($response, $coin) : TickerData
    {
        $response = Json::decode($response);

        return new TickerData([
            'src' => $response['data']['symbol'],
            'price' => $response['data']['quotes'][self::DEST_CURRENCY]['price'],
            'dst' => self::DEST_CURRENCY
        ]);
    }
}