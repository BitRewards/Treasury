<?php
namespace common\helpers;

use yii\base\Model;
use yii\helpers\Json;

class ThrowableModelErrors
{
    public static function jsonMessage(Model $model)
    {
        return Json::encode($model->getErrors());
    }
}