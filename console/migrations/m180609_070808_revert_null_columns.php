<?php

use console\components\Migration;

/**
 * Class m180609_070808_revert_null_columns
 */
class m180609_070808_revert_null_columns extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->execute('ALTER TABLE {{%user}} ALTER COLUMN api_key SET NOT NULL');
        $this->execute('ALTER TABLE {{%wallet}} ALTER COLUMN withdraw_key SET NOT NULL');

        $this->createIndex('uq-idx-user-api_key', '{{%user}}', 'api_key', true);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropIndex('uq-idx-user-api_key', '{{%user}}');
        $this->alterColumn('{{%user}}', 'api_key', 'DROP NOT NULL');
        $this->alterColumn('{{%wallet}}', 'withdraw_key', 'DROP NOT NULL');
    }
}
