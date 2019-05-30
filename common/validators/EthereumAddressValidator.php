<?php
namespace common\validators;

use yii\validators\Validator;

class EthereumAddressValidator extends Validator
{
    public function validateAttribute($model, $attribute)
    {
        $model->$attribute = trim(mb_strtolower($model->$attribute));
        if (!preg_match('/^(0x)[0-9a-f]{40}$/',$model->$attribute)) {
            $model->addError($attribute, 'Invalid address');
        }
    }
}
