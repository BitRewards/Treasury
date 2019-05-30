<?php
namespace common\exceptions;

use yii\base\Exception;

class InvalidBlockchainResponse extends Exception
{
    const CODE_NOT_ENOUGH_FUNDS = 101;
}
