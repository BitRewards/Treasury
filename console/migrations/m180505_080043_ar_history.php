<?php

use console\components\Migration;

/**
 * Class m180505_080043_ar_history
 */
class m180505_080043_ar_history extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable('{{%history}}', [
            'id' => $this->primaryKey(),
            'classname' => $this->string()->notNull(),
            'model_id' => $this->integer()->notNull(),
            'type' => $this->smallInteger(),
            'data' => $this->json(),
            'created_at' => $this->integer()
        ]);

        $this->createIndex('idx-history-classname', '{{%history}}', 'classname');
        $this->createIndex('idx-history-model_id', '{{%history}}', 'model_id');
        $this->createIndex('idx-history-type', '{{%history}}', 'type');
        $this->createIndex('idx-history-created_at', '{{%history}}', 'created_at');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropTable('{{%history}}');
    }

}
