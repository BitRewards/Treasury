<?php
namespace common\services\Fiat\Tickers\Coin;

use common\models\TickerData;
use common\services\Fiat\Tickers\AbstractTicker;
use common\services\Fiat\Tickers\InvalidTickerResponse;
use yii\base\InvalidConfigException;
use yii\helpers\Json;

class HitBtcService extends AbstractTicker
{
    public $baseUrl = 'https://api.hitbtc.com/';


    public function requestTickerData($coin)
    {
        return $this->queryApi('api/2/public/ticker/' . $coin);
    }

    /**
     * @param $currencyFrom
     * @param $currencyTo
     * @return TickerData|null
     * @throws InvalidTickerResponse
     * @throws InvalidConfigException
     */
    public function getExchangeRate($currencyFrom, $currencyTo)
    {
        $response = $this->requestTickerData($currencyFrom . $currencyTo);

        if (empty($response)) {
            throw new InvalidTickerResponse('Empty response from ticker');
        }

        if (!empty($response['error'])) {
            throw new InvalidTickerResponse('Empty response from ticker');
        }

        return $this->convertResponseExtended($response, $currencyFrom, $currencyTo);
    }

    /**
     * @param $response
     * @param $currencyFrom
     * @param $currencyTo
     * @return TickerData
     * @throws InvalidTickerResponse
     * @throws InvalidConfigException
     */
    public function convertResponseExtended($response, $currencyFrom, $currencyTo)
    {
        $data = $this->convertResponse($response, null);
        $data->src = $currencyFrom;
        $data->dst = $currencyTo;

        return $data;
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
            'src' => null,
            'price' => $response['last'],
            'dst' => null
        ]);
    }
}