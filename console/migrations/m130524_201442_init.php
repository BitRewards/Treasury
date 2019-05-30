<?php

class m130524_201442_init extends \console\components\Migration
{
    /**
     * @return bool|void
     * @throws \yii\base\Exception
     */
    public function safeUp()
    {
        $this->_createAuth();
        $this->_createEtheriumRelated();
        $this->_createTokenRelated();
    }

    /**
     * @return bool|void
     */
    public function safeDown()
    {
        $this->_dropTokenRelated();
        $this->_dropEtheriumRelated();
        $this->_dropAuth();
    }

    /**
     *
     */
    private function _createAuth()
    {
        $this->createTable('{{%user}}', [
            'id' => $this->primaryKey(),
            'crm_user_id' => $this->integer()->notNull()->unique(),
            'title' => $this->string(),
            'api_key' => $this->string(32)->notNull(),

            'status' => $this->smallInteger()->notNull()->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx-user-api_key', '{{%user}}', 'api_key');
        $this->createIndex('idx-user-status', '{{%user}}', 'status');
        $this->createIndex('idx-user-created_at', '{{%user}}', 'created_at');
        $this->createIndex('idx-user-updated_at', '{{%user}}', 'updated_at');
    }

    /**
     * @throws \yii\base\Exception
     */
    private function _createEtheriumRelated()
    {
        $this->createTable('{{%wallet}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull()->unique(),
            'address' => $this->string()->unique(),
            'status' => $this->smallInteger()->notNull()->defaultValue(0),
            'callback_url' => $this->text(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull()
        ]);

        $this->createIndex('idx-wallet-status', '{{%wallet}}', 'status');
        $this->createIndex('idx-wallet-created_at', '{{%wallet}}', 'created_at');
        $this->createIndex('idx-wallet-updated_at', '{{%wallet}}', 'updated_at');

        $this->addForeignKey('fk-wallet-user_id-user-id', '{{%wallet}}', 'user_id', '{{%user}}', 'id');

        $this->createTable('{{%withdrawal}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'dest_address' => $this->string()->notNull(),
            'currency' => $this->smallInteger()->notNull()->defaultValue(0),
            'amount' => $this->decimal(30, 18)->notNull(),
            'callback_url' => $this->string(),
            'tx_id' => $this->string()->unique(),
            'block_number' => $this->integer(),
            'gas_price' => $this->decimal(30, 18),
            'gas_limit' => $this->decimal(30, 18),
            'eth_fee' => $this->decimal(30, 18),
            'blockchain_response' => $this->json(),
            'status' => $this->smallInteger()->notNull()->defaultValue(0),
            'attempts_count' => $this->integer()->notNull()->defaultValue(0),
            'next_attempt_at' => $this->integer(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull()
        ]);

        $this->createIndex('idx-withdrawal-user_id', '{{%withdrawal}}', 'user_id');
        $this->createIndex('idx-withdrawal-dest_address', '{{%withdrawal}}', 'dest_address');
        $this->createIndex('idx-withdrawal-tx_id', '{{%withdrawal}}', 'tx_id');
        $this->createIndex('idx-withdrawal-status', '{{%withdrawal}}', 'status');
        $this->createIndex('idx-withdrawal-amount', '{{%withdrawal}}', 'amount');
        $this->createIndex('idx-withdrawal-block_number', '{{%withdrawal}}', 'block_number');
        $this->createIndex('idx-withdrawal-created_at', '{{%withdrawal}}', 'created_at');
        $this->createIndex('idx-withdrawal-updated_at', '{{%withdrawal}}', 'updated_at');

        $this->addForeignKey('fk-withdrawal-user_id-user-id', '{{%withdrawal}}', 'user_id', '{{%user}}', 'id');
    }

    /**
     * @inheritdoc
     */
    public function _createTokenRelated()
    {
        $this->createTable('{{%token_transfer}}', [
            'id' => $this->primaryKey(),
            'tx_id' => $this->string()->notNull()->unique(),
            'wallet_id' => $this->integer(),
            'src_address' => $this->string()->notNull(),
            'dest_address' => $this->string()->notNull(),
            'token_name' => $this->string()->notNull()->defaultValue('BIT'),
            'amount' => $this->decimal(30, 18),
            'data' => $this->json(),
            'block_number' => $this->integer()->notNull(),
            'gas_price' => $this->decimal(30, 18),
            'gas_limit' => $this->decimal(30, 18),
            'eth_fee' => $this->decimal(30, 18),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx-token_transfer-wallet_id', '{{%token_transfer}}', 'wallet_id');
        $this->createIndex('idx-token_transfer-tx_id', '{{%token_transfer}}', 'tx_id');
        $this->createIndex('idx-token_transfer-src_address', '{{%token_transfer}}', 'src_address');
        $this->createIndex('idx-token_transfer-dest_address', '{{%token_transfer}}', 'dest_address');
        $this->createIndex('idx-token_transfer-token_name', '{{%token_transfer}}', 'token_name');
        $this->createIndex('idx-token_transfer-amount', '{{%token_transfer}}', 'amount');
        $this->createIndex('idx-token_transfer-block_number', '{{%token_transfer}}', 'block_number');
        $this->createIndex('idx-token_transfer-created_at', '{{%token_transfer}}', 'created_at');
        $this->createIndex('idx-token_transfer-updated_at', '{{%token_transfer}}', 'updated_at');

        $this->addForeignKey('fk-token_transfer-wallet_id-wallet-id', '{{%token_transfer}}', 'wallet_id', '{{%wallet}}', 'id');
    }

    /**
     *
     */
    private function _dropAuth()
    {
        $this->dropTable('{{%user}}');
    }

    /**
     *
     */
    public function _dropEtheriumRelated()
    {
        $this->dropForeignKey('fk-withdrawal-user_id-user-id', '{{%withdrawal}}');
        $this->dropTable('{{%withdrawal}}');

        $this->dropForeignKey('fk-wallet-user_id-user-id', '{{%wallet}}');
        $this->dropTable('{{%wallet}}');
    }

    /**
     *
     */
    public function _dropTokenRelated()
    {
        $this->dropForeignKey('fk-token_transfer-wallet_id-wallet-id', '{{%token_transfer}}');
        $this->dropTable('{{%token_transfer}}');
    }
}
