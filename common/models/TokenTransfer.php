<?php

namespace common\models;

use common\behaviors\HistoryBehavior;
use common\validators\EthereumAddressValidator;
use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "token_transfer".
 *
 * @property int $id
 * @property int $wallet_id
 * @property string $src_address
 * @property string $dest_address
 * @property string $token_name
 * @property string $amount
 * @property array $data
 * @property array $extra_data
 * @property int $block_number
 * @property string $gas_price
 * @property string $gas_limit
 * @property string $eth_fee
 * @property int $created_at
 * @property int $updated_at
 * @property int $callback_id
 *
 * @property Wallet $wallet
 */
class TokenTransfer extends \yii\db\ActiveRecord
{
    const TOKEN_NAME_BIT = 'BIT';
    const TOKEN_NAME_BOKKY = 'BOKKY';

    const TOKEN_EVENT_BITTRANSFER = 'BITTransfer';
    const TOKEN_EVENT_TRANSFER = 'Transfer';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'token_transfer';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['wallet_id', 'block_number', 'callback_id'], 'integer'],
            [['src_address', 'dest_address', 'block_number'], 'required'],
            [['src_address', 'dest_address'], EthereumAddressValidator::class],
            [['amount', 'gas_price', 'gas_limit', 'eth_fee'], 'number'],
//            [['data'], 'string'],
            [['data', 'extra_data'], 'safe'],
            [['token_name'], 'string', 'max' => 255],
            [['wallet_id'], 'exist', 'skipOnError' => true, 'targetClass' => Wallet::class, 'targetAttribute' => ['wallet_id' => 'id']],
        ];
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
            HistoryBehavior::class
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'wallet_id' => Yii::t('app', 'Wallet ID'),
            'src_address' => Yii::t('app', 'Src Address'),
            'dest_address' => Yii::t('app', 'Dest Address'),
            'token_name' => Yii::t('app', 'Token Name'),
            'amount' => Yii::t('app', 'Amount'),
            'data' => Yii::t('app', 'Data'),
            'block_number' => Yii::t('app', 'Block Number'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWallet()
    {
        return $this->hasOne(Wallet::class, ['id' => 'wallet_id']);
    }
}
