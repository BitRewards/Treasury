<?php

use console\components\Migration;

/**
 * Class m180510_043913_wallet_withdraw_key
 */
class m180510_043913_wallet_withdraw_key extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('{{%wallet}}', 'withdraw_key', $this->string()->notNull());

        $this->createIndex('idx-wallet-withdraw_key', '{{%wallet}}', 'withdraw_key');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn('{{%wallet}}', 'withdraw_key');
    }
}
