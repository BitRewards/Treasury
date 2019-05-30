<?php
namespace common\traits;

trait TypedModel
{
    public static function getTypes()
    {
        return self::$TYPES;
    }

    public function getTypeLabel()
    {
        return self::getTypes()[$this->type] ?? null;
    }
}