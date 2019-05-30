<?php

namespace common\models;

use common\validators\JsonValidator;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\helpers\Json;

/**
 * This is the model class for table "history".
 *
 * @property int $id
 * @property int $classname
 * @property int $model_id
 * @property int $type
 * @property array $data
 * @property int $created_at
 *
 */
class History extends \yii\db\ActiveRecord
{
    const EVENT_RECORD_INSERTED = 0;
    const EVENT_RECORD_UPDATED = 1;
    const EVENT_RECORD_DELETED = 2;

    public static $EVENT_TYPES = [
        self::EVENT_RECORD_INSERTED => 'объект создан',
        self::EVENT_RECORD_UPDATED => 'объект обновлен',
        self::EVENT_RECORD_DELETED => 'объект удален',
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'history';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['model_id', 'type'], 'integer'],
            [['classname', 'model_id'], 'required'],
            [['classname'], 'string'],
//            [['data'], JsonValidator::class]
            ['data', 'safe']
        ];
    }

    public function behaviors()
    {
        return \yii\helpers\ArrayHelper::merge(
            [
                [
                    'class' => \yii\behaviors\TimestampBehavior::class,
                    'updatedAtAttribute' => false
                ],
            ],

            parent::behaviors()
        );
    }
}
