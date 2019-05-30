<?php

namespace common\models;

use common\behaviors\HistoryBehavior;
use common\behaviors\WithdrawalStatusBehavior;
use common\exceptions\NotImplementedException;
use common\exceptions\ValidationException;
use common\helpers\ThrowableModelErrors;
use common\helpers\WeiHelper;
use common\services\GiftdService;
use common\validators\EthereumAddressValidator;
use common\validators\EthereumTxValidator;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

/**
 * This is the model class for table "withdrawal".
 *
 * @property int $id
 * @property int $user_id
 * @property string $dest_address
 * @property int $currency
 * @property string $amount
 * @property string $callback_url
 * @property string $tx_id
 * @property int $block_number
 * @property string $gas_price
 * @property string $gas_limit
 * @property string $eth_fee
 * @property string $blockchain_response
 * @property int $status
 * @property int $attempts_count
 * @property int $next_attempt_at
 * @property int $created_at
 * @property int $updated_at
 * @property int $callback_id
 *
 * @property User $user
 */
class Withdrawal extends \yii\db\ActiveRecord
{
    const STATUS_NOT_PROCESSED = 0;
    const STATUS_INITIATED = 1;
    const STATUS_PROCESSED = 2;
    const STATUS_CONFIRMED = 3;
    const STATUS_INVALID_BLOCKCHAIN_RESPONSE = 4;
    const STATUS_FAILED = 5;
    const STATUS_WAITING_RETRY = 6;

    const CURRENCY_ETH = 0;
    const CURRENCY_BIT = 1;

    const MAX_WITHDRAWAL_ATTEMPTS = 30;

    const CHECK_STATUS_DELAY = 1 * 60;

    const DELAY_GRID = [
        1 => 1 * 60, // 1 minute
        2 => 5 * 60, // 5 minutes
        3 => 10 * 60, // 10 minutes
        4 => 30 * 60, // 30 minutes
        5 => 60 * 60, // 1 hour
        6 => 240 * 60, // 4 hours
        7 => 1440 * 60 // 24 hours
    ];


    public static $STATUSES_FOR_CALLBACK = [
        self::STATUS_CONFIRMED => 'confirmed',
        self::STATUS_FAILED => 'failed',
    ];

    public static $CURRENCY_LIST = [
        self::CURRENCY_BIT => 'BIT',
        self::CURRENCY_ETH => 'ETH'
    ];

    /**
     * @param $attempt
     * @return int
     */
    public static function getNextAttemptTimestamp($attempt)
    {
        $now = time();

        if (!$attempt) {
            return $now;
        }

        return $now + (self::DELAY_GRID[$attempt] ?? array_slice(self::DELAY_GRID, -1));
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'withdrawal';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'dest_address', 'amount'], 'required'],
            [['user_id', 'currency', 'status', 'block_number', 'callback_id'], 'integer'],
            [['amount', 'gas_price', 'gas_limit', 'eth_fee'], 'number'],
            [['blockchain_response'], 'string'],
            [['dest_address', 'tx_id'], 'string', 'max' => 255],
            [['dest_address'], EthereumAddressValidator::class],
            [['tx_id'], EthereumTxValidator::class],
            ['currency', 'in', 'range' => array_keys(self::$CURRENCY_LIST)],
            ['callback_url', 'string'],
            [['tx_id'], 'unique'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
            WithdrawalStatusBehavior::class,
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
            'user_id' => Yii::t('app', 'User ID'),
            'dest_address' => Yii::t('app', 'Dest Address'),
            'currency' => Yii::t('app', 'Currency'),
            'amount' => Yii::t('app', 'Amount'),
            'callback_url' => Yii::t('app', 'Callback Url'),
            'tx_id' => Yii::t('app', 'Tx ID'),
            'gas_price' => Yii::t('app', 'Gas Price'),
            'gas_limit' => Yii::t('app', 'Gas Limit'),
            'eth_fee' => Yii::t('app', 'Eth Fee'),
            'blockchain_response' => Yii::t('app', 'Blockchain Response'),
            'status' => Yii::t('app', 'Status'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getStatusForCallback()
    {
        return self::$STATUSES_FOR_CALLBACK[$this->status] ?? null;
    }

    /**
     * @return bool|\common\models\Callback
     * @throws ValidationException
     */
    public function createAndSetCallback()
    {
        if (!$this->callback_url || $this->callback_id) {
            return false;
        }

        $callback = new Callback();
        $callback->user_id = $this->user_id;
        $callback->url = $this->callback_url;
        $callback->params = [
            'id' => $this->id,
            'status' => $this->getStatusForCallback(),
            'tx_id' => $this->tx_id,
        ];

        if (!$callback->save()) {
            throw new ValidationException(ThrowableModelErrors::jsonMessage($callback));
        }

        $this->callback_id = $callback->id;

        if (!$this->save()) {
            throw new ValidationException(ThrowableModelErrors::jsonMessage($this));
        }
    }

    /**
     * @return bool|\common\models\Callback
     * @throws ValidationException
     */
    public function createLowBalanceCallback()
    {
        if (empty($this->user->crm_user_id) || !$this->callback_url || $this->callback_id) {
            return false;
        }

        $callback = new Callback();
        $callback->user_id = $this->user_id;
        $callback->url = Yii::$app->giftd->getBalanceNotificationEndpoint();
        $callback->params = [
            'id' => $this->id,
            'tx_id' => $this->tx_id,
        ];

        if (!$callback->save()) {
            throw new ValidationException(ThrowableModelErrors::jsonMessage($callback));
        }

        return true;
    }

    /**
     * @return bool
     * @throws \common\exceptions\InvalidBlockchainResponse
     */
    public function checkUserBalance()
    {
        $user = $this->user;

        $balance = Yii::$app->eth->getBalance($user->wallet->address);
        $amount_in_eth = $this->amount;

        switch ($this->currency)
        {
            case self::CURRENCY_ETH:
                return bccomp($balance['balanceEth'],  bcadd($amount_in_eth, Yii::$app->eth->getEthTransferFeeEstimate($user->id)), 18) >= 0;
                break;
            case self::CURRENCY_BIT:
                return bccomp($balance['balanceEth'], Yii::$app->eth->getTokenTransferEthFeeEstimate($user->id)) >= 0
                    && bccomp($balance['balanceBIT'], $amount_in_eth) >= 0;
                break;
            default:
                throw new NotImplementedException();
                break;
        }
    }
}
