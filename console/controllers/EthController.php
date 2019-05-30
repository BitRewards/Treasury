<?php
namespace console\controllers;

use common\helpers\ThrowableModelErrors;
use common\helpers\WeiHelper;
use common\models\Callback;
use common\models\TokenTransfer;
use common\models\Variable;
use common\models\Wallet;
use common\models\Withdrawal;
use Symfony\Component\Console\Tests\Helper\HelperTest;
use yii\base\InvalidConfigException;
use yii\base\InvalidValueException;
use yii\console\Controller;
use Yii;
use yii\helpers\Json;

class EthController extends Controller
{
    /**
     * @param $index
     * @return mixed
     * @throws \common\exceptions\InvalidBlockchainResponse
     */
    public function actionGetAddress($index)
    {
        return Yii::$app->eth->getAddress($index);
    }

    /**
     * @param $address
     * @return mixed
     * @throws \common\exceptions\InvalidBlockchainResponse
     */
    public function actionGetEthBalance($address)
    {
        echo WeiHelper::fromWei(Yii::$app->eth->getEthBalance($address));
    }

    /**
     * @param $index
     * @param $destAddress
     * @throws \common\exceptions\InvalidBlockchainResponse
     */
    public function actionSendEther($index, $destAddress)
    {
        $tx_id = Yii::$app->eth->sendEther($index, $destAddress, pow(10, 10));
        echo $tx_id;
    }


    /**
     * @param null $block
     * @throws \common\exceptions\InvalidBlockchainResponse
     */
    public function actionTransactionsList($block = null)
    {
        if (empty($block)) {
            $block = $currentBlock = Yii::$app->eth->getBlockNumber();
        }

        $transactions = Yii::$app->eth->getBlock($block);
        var_dump($transactions);
    }

    public function actionTokenTransfers($block = null)
    {
        if (empty($block)) {
            $block = $currentBlock = Yii::$app->eth->getBlockNumber();
        }


        $tokenTransactions = Yii::$app->eth->getTokenTransactions($block);
        var_dump($tokenTransactions);
    }

    /**
     * @param $transactions
     * @throws \common\exceptions\ValidationException
     */
    protected function _processTransactions($transactions)
    {
        foreach ($transactions as $transaction) {
            if (TokenTransfer::findOne(['tx_id' => $transaction['tx']])) {
                // record exists
                continue;
            }

            $transfer = new TokenTransfer();

            $wallet = Wallet::findOne(['address' => $transaction['to']]);
            $transfer->wallet_id = $wallet ? $wallet->id : null;

            $transfer->tx_id = $transaction['tx'];
            $transfer->src_address = $transaction['from'];
            $transfer->dest_address = $transaction['to'];
            $transfer->token_name = TokenTransfer::TOKEN_NAME_BIT;
            $transfer->amount = $transaction['amount'];
            $transfer->data = $transaction;
            $transfer->block_number = $transaction['blockNumber'];
            $transfer->extra_data = $transaction['extra_data'] ?? null;

            $transfer->gas_price = WeiHelper::fromWei($transaction['gas_price']);
            $transfer->gas_limit = WeiHelper::fromWei($transaction['gas_limit']);
            $transfer->eth_fee = WeiHelper::fromWei($transaction['eth_fee']);

            // add callback attributes
            if ($wallet) {
                // attach wallet callback
                $walletCallback = $wallet->createCallbackForTokenTransfer($transfer);

                if ($walletCallback) {
//                        throw new InvalidConfigException('Failed to create callback object');
                    $transfer->callback_id = $walletCallback->id;
                }
            }

            if (!$transfer->save()) {
                throw new InvalidValueException(ThrowableModelErrors::jsonMessage($transfer));
            }

        }
    }

    public function actionBulkTokenTransfers()
    {
        // get last parsed block
        $dbTransaction = \Yii::$app->db->beginTransaction();

        try {
            $variable = Variable::findBySql('SELECT * FROM {{%variable}} WHERE key = :key FOR UPDATE',
                [
                    ':key' => Variable::KEY_TOKEN_LAST_PARSED_BLOCK
                ])->one();

            if (!$variable) {
                // trying to create and then lock again
                $newlyCreatedOne = Variable::createKey(Variable::KEY_TOKEN_LAST_PARSED_BLOCK);
                $dbTransaction->commit();

                $dbTransaction = Yii::$app->db->beginTransaction();
                $variable = Variable::findBySql('SELECT * FROM {{%variable}} WHERE key = :key FOR UPDATE',
                    [
                        ':key' => $newlyCreatedOne->key
                    ])->one();
            }

            if (!$variable) {
                throw new InvalidValueException("Failed to obtain or create variable `" . Variable::KEY_TOKEN_LAST_PARSED_BLOCK . "`");
            }

            $currentBlock = Yii::$app->eth->getCurrentBlockNumber();

            $lastParsedBlock = (int)$variable->value;

            if (!$lastParsedBlock) {
                // for now parse last 1000 blocks
                $lastParsedBlock = $currentBlock - 1000;
            }

            $transactions = Yii::$app->eth->getTokenTransactions($lastParsedBlock, $currentBlock, TokenTransfer::TOKEN_EVENT_BITTRANSFER);
            $this->_processTransactions($transactions);

            $transactions = Yii::$app->eth->getTokenTransactions($lastParsedBlock, $currentBlock, TokenTransfer::TOKEN_EVENT_TRANSFER);
            $this->_processTransactions($transactions);

            $variable->value = $currentBlock;
            if (!$variable->save()) {
                throw new InvalidValueException(ThrowableModelErrors::jsonMessage($variable));
            }
            $dbTransaction->commit();
        } catch (\Throwable $e) {
            \Yii::error(['message' => $e->getMessage(), 'stack' => $e->getTrace()]);
            $dbTransaction->rollBack();
        }
        // get current block
        // list all transactions in between
        // process transactions
    }

    /**
     * @param $address
     * @throws \common\exceptions\InvalidBlockchainResponse
     */
    public function actionListTransactions($address) {
        $eth = Yii::$app->etherscan->listEthTransactions($address);
        $token = Yii::$app->etherscan->listTokenTransactions($address);
    }

    public function actionQueueBlockParser()
    {
        Yii::$app->queue->add('block-queue', []);
    }

    public function actionBlockWatch()
    {
        // get last parsed block
        $dbTransaction = \Yii::$app->db->beginTransaction();

        try {
            $variable = Variable::findBySql('SELECT * FROM {{%variable}} WHERE key = :key FOR UPDATE',
                [
                    ':key' => Variable::KEY_LAST_QUEUED_BLOCK
                ])->one();

            if (!$variable) {
                // trying to create and then lock again
                $newlyCreatedOne = Variable::createKey(Variable::KEY_LAST_QUEUED_BLOCK);
                $dbTransaction->commit();

                $dbTransaction = Yii::$app->db->beginTransaction();
                $variable = Variable::findBySql('SELECT * FROM {{%variable}} WHERE key = :key FOR UPDATE',
                    [
                        ':key' => $newlyCreatedOne->key
                    ])->one();
            }

            if (!$variable) {
                throw new InvalidValueException("Failed to obtain or create variable `" . Variable::KEY_LAST_QUEUED_BLOCK . "`");
            }

            $currentBlock = Yii::$app->eth->getCurrentBlockNumber();

            $lastQueuedBlock = (int)$variable->value;

            if (!$lastQueuedBlock) {
                // for now parse last 1000 blocks
                $lastQueuedBlock = $currentBlock - 10;
            }

            $queued = false;
            if ($currentBlock && $currentBlock > $lastQueuedBlock) {
                $variable->value = $currentBlock;
                if (!$variable->save()) {
                    throw new InvalidValueException(ThrowableModelErrors::jsonMessage($variable));
                }

                $queued = true;

            }

            $dbTransaction->commit();

            if ($queued) {
                Yii::$app->queue->add('block-queue', []);
            }
        } catch (\Throwable $e) {
            \Yii::error(['message' => $e->getMessage(), 'stack' => $e->getTrace()]);
            $dbTransaction->rollBack();
        }
    }
}
