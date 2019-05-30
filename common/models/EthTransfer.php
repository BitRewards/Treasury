<?php

namespace common\models;

use common\behaviors\HistoryBehavior;
use common\validators\EthereumAddressValidator;
use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "eth_transfer".
 *
 * @property int $id
 * @property int $src_wallet_id
 * @property int $dst_wallet_id
 * @property string $src_address
 * @property string $dest_address
 * @property string $amount
 * @property array $data
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
class EthTransfer extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'eth_transfer';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['src_wallet_id', 'dst_wallet_id', 'block_number', 'callback_id'], 'integer'],
            [['src_address', 'dest_address', 'block_number'], 'required'],
            [['src_address', 'dest_address'], EthereumAddressValidator::class],
            [['amount', 'gas_price', 'gas_limit', 'eth_fee'], 'number'],
//            [['data'], 'string'],
            [['data'], 'safe'],
            [['src_wallet_id'], 'exist', 'skipOnError' => true, 'targetClass' => Wallet::class, 'targetAttribute' => ['src_wallet_id' => 'id']],
            [['dst_wallet_id'], 'exist', 'skipOnError' => true, 'targetClass' => Wallet::class, 'targetAttribute' => ['dst_wallet_id' => 'id']],
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
            'src_wallet_id' => Yii::t('app', 'Src Wallet ID'),
            'dst_wallet_id' => Yii::t('app', 'Dest Wallet ID'),
            'src_address' => Yii::t('app', 'Src Address'),
            'dest_address' => Yii::t('app', 'Dest Address'),
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
    public function getSrcWallet()
    {
        return $this->hasOne(Wallet::class, ['id' => 'src_wallet_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDstWallet()
    {
        return $this->hasOne(Wallet::class, ['id' => 'dst_wallet_id']);
    }
}
