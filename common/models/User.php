<?php
namespace common\models;

use common\traits\JWTAuthTrait;
use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * User model
 *
 * @property integer $id
 * @property string $title
 * @property string $api_key
 * @property integer $crm_user_id
 * @property integer $status
 * @property integer $created_at
 * @property integer $updated_at
 * @property Wallet $wallet
 * @property Withdrawal[] $withdrawals
 */
class User extends ActiveRecord implements IdentityInterface
{
    use JWTAuthTrait;

    const STATUS_DELETED = 0;
    const STATUS_ACTIVE = 1;

    const SCENARIO_DEFAULT = 'default';
    const SCENARIO_NO_CRM_USER_ID = 'no_crm_user_id';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%user}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['crm_user_id'], 'required', 'on' => self::SCENARIO_DEFAULT],
            [['api_key'], 'required'],
            ['crm_user_id', 'integer'],
            [['title'], 'string', 'max' => 255],
            [['api_key'], 'string', 'max' => 32],
            ['status', 'default', 'value' => self::STATUS_ACTIVE],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_DELETED]],
            [['crm_user_id'], 'unique'],
            [['api_key'], 'unique'],
        ];
    }

    /**
     * @return array
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_DEFAULT] = ['*'];
        $scenarios[self::SCENARIO_NO_CRM_USER_ID] = ['*'];
        return $scenarios;
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    public static function findActive()
    {
        return static::find();//->where(['status' => self::STATUS_ACTIVE]);
    }

    public static function findOneWhere($params = [])
    {
        return static::findActive()->andWhere($params)->one();
    }

    public static function findByCrmId($crm_user_id)
    {
        return static::findOneWhere(['crm_user_id' => $crm_user_id]);
    }

    /**
     * Finds user by api_key
     *
     * @param string $api_key
     * @return static|null
     */
    public static function findByApiKey($api_key)
    {
        return !empty($api_key) ? static::findOneWhere(['api_key' => $api_key]) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->auth_key === $authKey;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWallet()
    {
        return $this->hasOne(Wallet::class, ['user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWithdrawals()
    {
        return $this->hasMany(Withdrawal::class, ['user_id' => 'id']);
    }

}
