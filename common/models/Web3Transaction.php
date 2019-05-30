<?php
namespace common\models;

use common\helpers\WeiHelper;
use common\validators\EthereumAddressValidator;
use common\validators\EthereumTxValidator;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\i18n\Formatter;

/**
 * Class Web3Transaction
 * @property int $blockNumber
 * @property string $hash
 * @property int $nonce
 * @property string $blockHash
 * @property int $transactionIndex
 * @property string $from
 * @property string $to
 * @property string $value
 * @property string $amount
 * @property double $gas
 * @property double $gasPrice
 * @package common\models
 */
class Web3Transaction extends Model
{
    public $hash;
    public $nonce;
    public $blockHash;
    public $blockNumber;
    public $transactionIndex;
    public $from;
    public $to;
    public $value;
    public $gas;
    public $gasPrice;
    public $amount;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['hash', 'blockNumber', 'from', 'to'], 'required'],
            [['blockNumber', 'nonce', 'transactionIndex'], 'integer'],
            [['from', 'to'], EthereumAddressValidator::class],
            [['gas', 'gasPrice'], 'number'],
            [['amount', 'value'], 'string'],
            [['hash', 'blockHash'], EthereumTxValidator::class]
        ];
    }
}