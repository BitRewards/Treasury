<?php

namespace common\models;

use common\exceptions\ValidationException;
use common\helpers\ThrowableModelErrors;
use common\validators\EthereumAddressValidator;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\helpers\Json;

/**
 * This is the model class for table "wallet".
 *
 * @property int $id
 * @property int $user_id
 * @property string $address
 * @property string $withdraw_key
 * @property string $callback_url
 * @property int $status
 * @property int $created_at
 * @property int $updated_at
 *
 * @property TokenTransfer[] $tokenTransfers
 * @property User $user
 */
class Wallet extends \yii\db\ActiveRecord
{
    const SCENARIO_DEFAULT = 'default';
    const SCENARIO_READONLY = 'readonly';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'wallet';
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'withdraw_key', 'address'], 'required', 'on' => self::SCENARIO_DEFAULT],
            [['user_id'], 'required', 'on' => self::SCENARIO_READONLY],
            [['user_id', 'status'], 'integer'],
            [['address', 'withdraw_key'], 'string', 'max' => 255],
            ['callback_url', 'string'],
            [['user_id'], 'unique'],
            [['address'], 'unique'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
            ['address', EthereumAddressValidator::class]
        ];
    }

    /**
     * @return array
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_DEFAULT] = ['*'];
        $scenarios[self::SCENARIO_READONLY] = ['*'];
        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'address' => Yii::t('app', 'Address'),
            'status' => Yii::t('app', 'Status'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTokenTransfers()
    {
        return $this->hasMany(TokenTransfer::class, ['wallet_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * @return bool|\common\models\Callback
     * @throws ValidationException
     */
    public function createCallbackForTokenTransfer(TokenTransfer $transfer)
    {
        if (!$this->callback_url || $transfer->callback_id) {
            return false;
        }

        $callback = new Callback();
        $callback->url = $this->callback_url;
        $callback->user_id = $transfer->wallet ? $transfer->wallet->user_id : null;
        $callback->params = [
            'amount' => $transfer->amount,
            'treasury_receiver_address' => $transfer->wallet->address,
            'treasury_tx_hash' => $transfer->tx_id,
            'treasury_data' => $transfer->extra_data,
            'treasury_sender_address' => $transfer->src_address
        ];

        if (!$callback->save()) {
            throw new ValidationException(ThrowableModelErrors::jsonMessage($callback));
        }

        return $callback;
    }
}
