<?php
namespace common\helpers;

class WeiHelper
{
    /**
     * @param string $amount
     * @return string
     */
    public static function inWei($amount) {
        return bcmul($amount, bcpow(10, 18));
    }

    /**
     * @param string $amountInWei
     * @return float
     */
    public static function fromWei($amountInWei) : string {
        return bcmul(number_format($amountInWei, 18, '.', ''), bcpow(10, -18, 18), 18);
    }

    public static function formatValue($amountInEth)
    {
        return rtrim(rtrim($amountInEth, '0'), '.,');
    }
}
