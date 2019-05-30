<?php

namespace common\services;

use common\exceptions\NotImplementedException;
use common\services\Fiat\Tickers\Coin\CoinmarketcapService;
use common\services\Fiat\Tickers\Coin\HitBtcService;
use common\services\Fiat\Tickers\Currency\ApiLayerService;
use common\services\Fiat\Tickers\InvalidTickerResponse;
use yii\base\Component;
use yii\base\InvalidValueException;

class FiatService extends Component
{
    const CURRENCY_RUB = 1;
    const CURRENCY_KZT = 2;
    const CURRENCY_EUR = 3;
    const CURRENCY_UAH = 4;
    const CURRENCY_USD = 5;
    const CURRENCY_GEL = 6;
    const CURRENCY_BYN = 7;

    public static $CURRENCY_LIST = [
        self::CURRENCY_RUB => 'RUB',
        self::CURRENCY_KZT => 'KZT',
        self::CURRENCY_EUR => 'EUR',
        self::CURRENCY_UAH => 'UAH',
        self::CURRENCY_USD => 'USD',
        self::CURRENCY_GEL => 'GEL',
        self::CURRENCY_BYN => 'BYN'
    ];

    public static $COIN_LIST = [
        'ETH', 'BIT', 'BTC'
    ];

    const FIXED_BIT_TO_ETH_RATE = '0.00003472';
    const FIAT_EXTRA_PERCENT = '5';

    public function getExchangeRate($currencyFrom, $currencyTo, $forceUpdate = false)
    {
        $key = 'fiat_rate_' . $currencyFrom . '_' . $currencyTo;

        if ($currencyFrom === $currencyTo) {
            return 1;
        }

        if (!$forceUpdate && $rate = \Yii::$app->cache->get($key)) {
            return $rate;
        }

        try {
            $from_usd_rate = $this->getUSDRate($currencyFrom, $forceUpdate);
            $to_usd_rate = $this->getUSDRate($currencyTo, $forceUpdate);

            $value = 1/$from_usd_rate * $to_usd_rate;

            $value *= (1 + self::FIAT_EXTRA_PERCENT / 100);
            \Yii::$app->cache->set($key, $value, 24 * 3600);

            return $value;
        } catch (\Throwable $e) {
            \Yii::error($e->getMessage());
            var_dump($e->getMessage());

            return null;
        }
    }

    protected function isCoin($currency)
    {
        return in_array($currency, self::$COIN_LIST, true);
    }

    protected function isCurrency($currency)
    {
        return in_array($currency, self::$CURRENCY_LIST, true);
    }

    /**
     * @param $coin
     * @return float|int|null
     * @throws \yii\base\InvalidConfigException
     */
    protected function getCoinRate($coin)
    {
        try {
            $coinService = new CoinmarketcapService();
            if ($coin === 'BIT') {
                $eth_to_usd = $coinService->getTicker('ETH');
                $bit_to_eth = (new HitBtcService())->getExchangeRate('BIT', 'ETH');

                $bit_to_usd = $eth_to_usd->price * $bit_to_eth->price;
                return 1 / $bit_to_usd;
            } else {
                $coin_to_usd = $coinService->getTicker($coin);
                return 1 / $coin_to_usd->price;
            }
        } catch (InvalidTickerResponse $e) {
            \Yii::error($e->getMessage());

            return null;
        }
    }

    protected function getFiatRate($currency)
    {
        if ($currency === 'USD') {
            return 1;
        }

        $currencyService = new ApiLayerService();

        try {
            $usd_to_fiat = $currencyService->getTicker($currency);
            return $usd_to_fiat->price;
        } catch (InvalidTickerResponse $e) {
            \Yii::error($e->getMessage());

            return null;
        }
    }

    /**
     * @param $currency
     * @return float|int|null
     */
    public function getUSDRate($currency, $forceUpdate)
    {
        $key = 'usd_rate_' . $currency;

        if (!$forceUpdate && $rate = \Yii::$app->cache->get($key)) {
            return $rate;
        }

        if ($this->isCoin($currency)) {
            $value = $this->getCoinRate($currency);
        } else if ($this->isCurrency($currency)) {
            $value = $this->getFiatRate($currency);
        } else {
            throw new NotImplementedException('Undefined currency: ' . $currency);
        }

        if (empty($value)) {
            throw new InvalidValueException('Invalid rate response');
        }

        \Yii::$app->cache->set($key, $value, 24 * 3600);

        return $value;

    }

    public function getBitExchangeRate($currencyFrom, $forceUpdate = false)
    {
        return $this->getRate($currencyFrom, $forceUpdate);
    }

    public function getRate($currency, $forceUpdate = false)
    {
        $key = 'fiat_rate_' . $currency;

        if (!$forceUpdate && $rate = \Yii::$app->cache->get($key)) {
            return $rate;
        }

        $coinService = new CoinmarketcapService();
        $currencyService = new ApiLayerService();

        try {
            $eth_to_usd = $coinService->getTicker('ETH');
            $bit_to_eth = self::FIXED_BIT_TO_ETH_RATE;
            $usd_to_fiat = $currencyService->getTicker($currency);

            $value = $eth_to_usd->price * $bit_to_eth * $usd_to_fiat->price;
            $value *= (1 + self::FIAT_EXTRA_PERCENT / 100);
            \Yii::$app->cache->set($key, $value, 24 * 3600);

            return $value;
        } catch (InvalidTickerResponse $e) {
            \Yii::error($e->getMessage());

            return null;
        }

    }

}
