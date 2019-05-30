<?php

use console\components\Migration;

/**
 * Class m180505_192734_callbacks
 */
class m180505_192734_callbacks extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable('{{%callback}}', [
            'id' => $this->primaryKey(),
            'url' => $this->text()->notNull(),
            'method' => $this->smallInteger()->notNull()->defaultValue(0),
            'params' => $this->json(),
            'attempts_count' => $this->integer()->notNull()->defaultValue(0),
            'status' => $this->smallInteger()->notNull()->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx-callback-created_at', '{{%callback}}', 'created_at');
        $this->createIndex('idx-callback-updated_at', '{{%callback}}', 'updated_at');
        $this->createIndex('idx-callback-attempts_count', '{{%callback}}', 'attempts_count');
        $this->createIndex('idx-callback-status', '{{%callback}}', 'status');

        $this->addColumn('{{%withdrawal}}', 'callback_id', $this->integer());
        $this->createIndex('idx-withdrawal-callback_id', '{{%withdrawal}}', 'callback_id');
        $this->addForeignKey('fk-withdrawal-callback_id-callback-id', '{{%withdrawal}}', 'callback_id', '{{%callback}}', 'id');

        $this->addColumn('{{%token_transfer}}', 'callback_id', $this->integer());
        $this->createIndex('idx-token_transfer-callback_id', '{{%token_transfer}}', 'callback_id');
        $this->addForeignKey('fk-token_transfer-callback_id-callback-id', '{{%token_transfer}}', 'callback_id', '{{%callback}}', 'id');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-token_transfer-callback_id-callback-id', '{{%token_transfer}}');
        $this->dropColumn('{{%token_transfer}}', 'callback_id');

        $this->dropForeignKey('fk-withdrawal-callback_id-callback-id', '{{%withdrawal}}');
        $this->dropColumn('{{%withdrawal}}', 'callback_id');


        $this->dropTable('{{%callback}}');
    }
}
