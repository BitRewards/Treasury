<?php

use console\components\Migration;

/**
 * Class m180512_200430_callback_user_id
 */
class m180512_200430_callback_user_id extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('{{%callback}}', 'user_id', $this->integer());

        $this->createIndex('idx-callback-user_id', '{{%callback}}', 'user_id');
        $this->addForeignKey('fk-callback-user_id-user-id', '{{%callback}}', 'user_id', '{{%user}}', 'id');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-callback-user_id-user-id', '{{%callback}}');
        $this->dropColumn('{{%callback}}', 'user_id');
    }
}
