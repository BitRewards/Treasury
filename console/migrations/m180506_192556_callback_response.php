<?php

use console\components\Migration;

/**
 * Class m180506_192556_callback_response
 */
class m180506_192556_callback_response extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('{{%callback}}', 'response', $this->json());
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn('{{%callback}}', 'response');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180506_192556_callback_response cannot be reverted.\n";

        return false;
    }
    */
}
