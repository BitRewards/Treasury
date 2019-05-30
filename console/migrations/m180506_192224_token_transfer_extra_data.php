<?php

use console\components\Migration;

/**
 * Class m180506_192224_token_transfer_extra_data
 */
class m180506_192224_token_transfer_extra_data extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('{{%token_transfer}}', 'extra_data', $this->json());
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn('{{%token_transfer}}', 'extra_data');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180506_192224_token_transfer_extra_data cannot be reverted.\n";

        return false;
    }
    */
}
