<?php

namespace console\components;

use yii\db\Migration as BaseMigration;

class Migration extends BaseMigration
{
    public $tableOptions = null;

    public function init() {
        parent::init();

        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $this->tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }
    }

    public function createTable($table, $columns, $options = null) {
        return parent::createTable($table, $columns, $options ?? $this->tableOptions);
    }
}
