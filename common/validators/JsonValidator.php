<?php
namespace common\validators;

use yii\base\InvalidArgumentException;
use yii\helpers\Json;
use yii\validators\Validator;

class JsonValidator extends Validator
{
    public function validateAttribute($model, $attribute)
    {
        try {
            Json::decode($model->$attribute, false);
        } catch (InvalidArgumentException $e) {
            $this->addError($model, $attribute, $e->getMessage());
        }
    }
}