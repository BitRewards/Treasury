<?php
namespace common\exceptions;

class NotEnoughBalanceException extends InvalidBlockchainResponse
{
    public $code = self::CODE_NOT_ENOUGH_FUNDS;
}
