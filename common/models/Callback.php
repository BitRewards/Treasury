<?php

namespace common\models;

use common\behaviors\HistoryBehavior;
use common\validators\JsonValidator;
use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "callback".
 *
 * @property int $id
 * @property string $url
 * @property int $method
 * @property array $params
 * @property array $response
 * @property int $attempts_count
 * @property int $status
 * @property int $created_at
 * @property int $updated_at
 * @property int $user_id
 *
 * @property TokenTransfer[] $tokenTransfers
 * @property Withdrawal[] $withdrawals
 * @property User $user
 */
class Callback extends \yii\db\ActiveRecord
{
    const METHOD_POST = 0;
    const METHOD_GET = 1;

    const STATUS_NOT_PROCESSED = 0;
    const STATUS_INITIATED = 1;
    const STATUS_PROCESSED = 2;
    const STATUS_FAILED = 3;
    const STATUS_WAITING_RETRY = 4;

    const MAX_CALLBACK_ATTEMPTS = 10;

    const REPLY_INTERVAL = 60*5;

    public static $METHODS = [
        self::METHOD_POST => 'POST',
        self::METHOD_GET => 'GET'
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'callback';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
            HistoryBehavior::class
        ];
    }

    public function getMethodName()
    {
        return self::$METHODS[$this->method];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['url'], 'required'],
            [['url'], 'string'],
            [['params', 'response'], 'safe'],
//            [['params'], JsonValidator::class],
//            [['method', 'attempts_count', 'status'], 'default', 'value' => 0],
            [['user_id', 'method', 'attempts_count', 'status'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'url' => Yii::t('app', 'Url'),
            'method' => Yii::t('app', 'Method'),
            'params' => Yii::t('app', 'Params'),
            'attempts_count' => Yii::t('app', 'Attempts Count'),
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
        return $this->hasMany(TokenTransfer::class, ['callback_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWithdrawals()
    {
        return $this->hasMany(Withdrawal::class, ['callback_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
