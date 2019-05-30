<?php
namespace common\models;

use common\helpers\WeiHelper;
use common\validators\EthereumAddressValidator;
use common\validators\EthereumTxValidator;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\i18n\Formatter;

/**
 * Class Transaction
 * @property int $blockNumber
 * @property int $timeStamp
 * @property string $hash
 * @property int $nonce
 * @property string $blockHash
 * @property int $transactionIndex
 * @property string $from
 * @property string $to
 * @property string $value
 * @property double $gas
 * @property double $gasPrice
 * @property double $gasUsed
 * @property int $isError
 * @property int $txreceipt_status;
 * @property string $contractAddress
 * @property string $tokenName
 * @property string $tokenSymbol
 * @property int $status
 *
 * @package common\models
 */
class Transaction extends Model
{
    public $blockNumber;
    public $timeStamp;
    public $hash;
    public $nonce;
    public $blockHash;
    public $transactionIndex;
    public $from;
    public $to;
    public $value;
    public $gas;
    public $gasPrice;
    public $isError;
    public $txreceipt_status;
    public $contractAddress;
    public $gasUsed;
    public $tokenName;
    public $tokenSymbol;
    public $status;

    public function fields()
    {
        return ArrayHelper::merge(parent::fields(), [
            'amount_with_name' => function (Transaction $model) {
                return WeiHelper::formatValue(WeiHelper::fromWei($model->value)) . ' ' . ($model->tokenSymbol ?? 'ETH');
            },
            'amount' => 'value',
            'created_at' => function (Transaction $model) {
                return (new \DateTime())->setTimestamp($model->timeStamp)->format('Y-m-d H:i:s');
            },
            'status' => function (Transaction $model) {
                return $model->status ?? 'success';
            }
        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['hash'], 'required'],
            [['blockNumber', 'timeStamp', 'nonce', 'transactionIndex', 'txreceipt_status', 'isError', 'status'], 'integer'],
            [['from', 'to', 'contractAddress'], EthereumAddressValidator::class],
            [['gas', 'gasPrice', 'gasUsed'], 'number'],
            [['tokenName', 'tokenSymbol', 'value'], 'string'],
            [['hash', 'blockHash'], EthereumTxValidator::class]
        ];
    }
}