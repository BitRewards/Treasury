<?php
namespace common\models;

use yii\base\Model;

class Partner extends Model
{
    public $id;
    public $title;

    public function rules()
    {
        return [
            ['id', 'required'],
            ['id', 'integer'],
            ['title', 'string', 'max' => 255]
        ];
    }
}
