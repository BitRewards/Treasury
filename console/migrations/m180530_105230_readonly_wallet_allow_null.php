<?php

use console\components\Migration;

/**
 * Class m180530_105230_readonly_wallet_allow_null
 */
class m180530_105230_readonly_wallet_allow_null extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->alterColumn('{{%user}}', 'crm_user_id', 'DROP NOT NULL');
        $this->alterColumn('{{%user}}', 'api_key', 'DROP NOT NULL');

        $this->alterColumn('{{%wallet}}', 'withdraw_key', 'DROP NOT NULL');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->execute('ALTER TABLE {{%user}} ALTER COLUMN crm_user_id SET NOT NULL, ALTER COLUMN api_key SET NOT NULL');
        $this->execute('ALTER TABLE {{%wallet}} ALTER COLUMN withdraw_key SET NOT NULL');
    }
}
