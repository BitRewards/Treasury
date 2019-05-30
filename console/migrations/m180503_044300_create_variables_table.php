<?php

/**
 * Handles the creation of table `variables`.
 */
class m180503_044300_create_variables_table extends \console\components\Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('variable', [
            'id' => $this->primaryKey(),
            'key' => $this->string()->notNull()->unique(),
            'value' => $this->text(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx-variable-created_at', '{{%variable}}', 'created_at');
        $this->createIndex('idx-variable-updated_at', '{{%variable}}', 'updated_at');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('variable');
    }
}
