<?php

namespace common\models;

use common\helpers\ThrowableModelErrors;
use Yii;
use yii\base\InvalidValueException;
use yii\behaviors\TimestampBehavior;
use yii\db\Exception;

/**
 * This is the model class for table "variable".
 *
 * @property int $id
 * @property string $key
 * @property string $value
 * @property int $created_at
 * @property int $updated_at
 */
class Variable extends \yii\db\ActiveRecord
{
    const KEY_TOKEN_LAST_PARSED_BLOCK = 'token-last-parsed-block';
    const KEY_LAST_QUEUED_BLOCK = 'last-queued-block';
    const KEY_LAST_PARSED_BLOCK = 'last-parsed-block';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'variable';
    }

    /**
     * @inheritdoc
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
            [['key'], 'required'],
            [['key'], 'string', 'max' => 255],
            [['key'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'key' => Yii::t('app', 'Key'),
            'value' => Yii::t('app', 'Value'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @param $key
     * @return Variable
     * @throws InvalidValueException
     */
    public static function createKey($key)
    {
        $entity = new static();
        $entity->key = $key;

        // just to be specific
        $entity->value = null;

        try {
            if ($entity->save()) {
                return $entity;
            }

            throw new InvalidValueException(ThrowableModelErrors::jsonMessage($entity));
        } catch (Exception $dbException) {
            // possible duplicate key issue - that's ok
            throw new InvalidValueException($dbException->getMessage());
        }
    }
}
