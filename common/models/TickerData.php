<?php

namespace common\models;

use yii\base\Model;

class TickerData extends Model
{
    public $src;
    public $price;
    public $dst;

    public function rules()
    {
        return [
            [['src', 'price', 'dst'], 'safe']
        ];
    }
}
