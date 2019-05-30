<?php

use console\components\Migration;

/**
 * Class m180628_050138_create_eth_transfers
 */
class m180628_050138_create_eth_transfers extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable('{{%eth_transfer}}', [
            'id' => $this->primaryKey(),
            'tx_id' => $this->string()->notNull()->unique(),
            'src_wallet_id' => $this->integer(),
            'dst_wallet_id' => $this->integer(),
            'src_address' => $this->string()->notNull(),
            'dest_address' => $this->string()->notNull(),
            'amount' => $this->decimal(30, 18),
            'data' => $this->json(),
            'block_number' => $this->integer()->notNull(),
            'gas_price' => $this->decimal(30, 18),
            'gas_limit' => $this->decimal(30, 18),
            'eth_fee' => $this->decimal(30, 18),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'callback_id' => $this->integer()
        ]);

        $this->createIndex('idx-eth_transfer-src_wallet_id', '{{%eth_transfer}}', 'src_wallet_id');
        $this->createIndex('idx-eth_transfer-dst_wallet_id', '{{%eth_transfer}}', 'dst_wallet_id');
        $this->createIndex('idx-eth_transfer-tx_id', '{{%eth_transfer}}', 'tx_id');
        $this->createIndex('idx-eth_transfer-src_address', '{{%eth_transfer}}', 'src_address');
        $this->createIndex('idx-eth_transfer-dest_address', '{{%eth_transfer}}', 'dest_address');
        $this->createIndex('idx-eth_transfer-amount', '{{%eth_transfer}}', 'amount');
        $this->createIndex('idx-eth_transfer-block_number', '{{%eth_transfer}}', 'block_number');
        $this->createIndex('idx-eth_transfer-created_at', '{{%eth_transfer}}', 'created_at');
        $this->createIndex('idx-eth_transfer-updated_at', '{{%eth_transfer}}', 'updated_at');
        $this->createIndex('idx-eth_transfer-callback_id', '{{%eth_transfer}}', 'callback_id');

        $this->addForeignKey('fk-eth_transfer-src_wallet_id-wallet-id', '{{%eth_transfer}}', 'src_wallet_id', '{{%wallet}}', 'id');
        $this->addForeignKey('fk-eth_transfer-dst_wallet_id-wallet-id', '{{%eth_transfer}}', 'dst_wallet_id', '{{%wallet}}', 'id');
        $this->addForeignKey('fk-eth_transfer-callback_id-callback-id', '{{%eth_transfer}}', 'callback_id', '{{%callback}}', 'id');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-eth_transfer-callback_id-callback-id', '{{%eth_transfer}}');
        $this->dropForeignKey('fk-eth_transfer-src_wallet_id-wallet-id', '{{%eth_transfer}}');
        $this->dropForeignKey('fk-eth_transfer-dst_wallet_id-wallet-id', '{{%eth_transfer}}');
        $this->dropTable('{{%eth_transfer}}');
    }
}
