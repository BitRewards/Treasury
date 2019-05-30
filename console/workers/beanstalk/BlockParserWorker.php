<?php

namespace console\workers\beanstalk;

use common\exceptions\InvalidBlockchainResponse;
use common\exceptions\ValidationException;
use common\helpers\ThrowableModelErrors;
use common\helpers\WeiHelper;
use common\models\Callback;
use common\models\EthTransfer;
use common\models\TokenTransfer;
use common\models\Transaction;
use common\models\Variable;
use common\models\Wallet;
use common\models\Web3Transaction;
use common\models\Withdrawal;
use Pheanstalk\Job;
use yii\base\InvalidArgumentException;
use yii\base\InvalidValueException;
use yii\db\Exception;

class BlockParserWorker extends \console\workers\beanstalk\BeanstalkController
{
    public function listenTubes()
    {
        return ['block-parser'];
    }

    /**
     * @param $transactions
     * @throws \common\exceptions\ValidationException
     */
    protected function _processTokenTransactions($transactions)
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

    /**
     * @param EthTransfer $transfer
     * @return bool|Callback
     * @throws ValidationException
     */
    protected function _createCallbackForEthTransfer(EthTransfer $transfer)
    {
        if ($transfer->callback_id) {
            return false;
        }

        $callback = new Callback();
        $callback->url = \Yii::$app->giftd->getEthTransferCallbackEndpoint();
        $callback->params = [
            'amount' => $transfer->amount,
            'treasury_receiver_address' => $transfer->dest_address,
            'treasury_tx_hash' => $transfer->tx_id,
            'treasury_sender_address' => $transfer->src_address,
        ];

        if (!$callback->save()) {
            throw new ValidationException(ThrowableModelErrors::jsonMessage($callback));
        }

        return $callback;
    }

    /**
     * @param $transactions
     * @throws \common\exceptions\ValidationException
     * @throws InvalidBlockchainResponse
     */
    protected function  _processEthTransactions($transactions)
    {
        foreach ($transactions as $transactionData) {
            $transaction = new Web3Transaction();
            if (!$transaction->load($transactionData, '') || !$transaction->validate()) {
//                \Yii::error(['message' => 'Failed to init Web3Transaction model', 'data' => $transactionData]);

                continue;
            }

            if (EthTransfer::findOne(['tx_id' => $transaction->hash])) {
                // record exists
                continue;
            }

            $transfer = new EthTransfer();

            $srcWallet = Wallet::findOne(['address' => $transaction->from]);
            $transfer->src_wallet_id = $srcWallet ? $srcWallet->id : null;

            $dstWallet = Wallet::findOne(['address' => $transaction->to]);
            $transfer->dst_wallet_id = $dstWallet ? $dstWallet->id : null;

            if ($srcWallet || $dstWallet) {
                $txData = \Yii::$app->eth->getTransactionByHash($transaction->hash);
            } else {
                continue;
            }

            $transfer->tx_id = $transaction->hash;
            $transfer->src_address = $transaction->from;
            $transfer->dest_address = $transaction->to;
            $transfer->amount = $transaction->amount;
            $transfer->data = $txData;
            $transfer->block_number = $transaction->blockNumber;

            $transfer->gas_price = WeiHelper::fromWei($txData['gas_price']);
            $transfer->gas_limit = WeiHelper::fromWei($txData['gas_limit']);
            $transfer->eth_fee = WeiHelper::fromWei($txData['eth_fee']);



            // add callback attributes
            $ethCallback = $this->_createCallbackForEthTransfer($transfer);
            if ($ethCallback) {
                $transfer->callback_id = $ethCallback->id;
            }

            if (!$transfer->save()) {
                throw new InvalidValueException(ThrowableModelErrors::jsonMessage($transfer));
            }

        }
    }

    /**
     * @param Job $job
     * @return string
     * @throws \yii\db\Exception
     */
    public function actionBlockParser(Job $job)
    {
        $blockNumber = (int)$job->getData();

        \Yii::info($blockNumber, 'BlockParser');

        $action = self::DELETE;

        $dbTransaction = \Yii::$app->db->beginTransaction();
        try {

            /* @var Variable $variable */
            $variable = Variable::findBySql('SELECT * FROM {{%variable}} WHERE key = :key FOR UPDATE',
                [
                    ':key' => Variable::KEY_LAST_PARSED_BLOCK
                ])->one();

            if (!$variable) {
                throw new InvalidValueException("Failed to obtain variable `" . Variable::KEY_LAST_PARSED_BLOCK . "`");
            }

            if ((int)$variable->value !== ($blockNumber - 1)) {
                throw new \UnexpectedValueException("Invalid block number #{$blockNumber}. Last parsed block #{$variable->value}");
            }

            // BIT Transfer
            $transactions = \Yii::$app->eth->getTokenTransactions($blockNumber, $blockNumber, TokenTransfer::TOKEN_EVENT_BITTRANSFER);
            $this->_processTokenTransactions($transactions);

            // ERC20 Token Transfer
            $transactions = \Yii::$app->eth->getTokenTransactions($blockNumber, $blockNumber, TokenTransfer::TOKEN_EVENT_TRANSFER);
            $this->_processTokenTransactions($transactions);

            $transactions = \Yii::$app->eth->getEthTransactions($blockNumber);
            $this->_processEthTransactions($transactions);

            $variable->value = $blockNumber;
            if (!$variable->save()) {
                throw new InvalidValueException(ThrowableModelErrors::jsonMessage($variable));
            }

            $dbTransaction->commit();

            \Yii::info($blockNumber . ' parsed', 'BlockParser');
            \Yii::$app->queue->add('block-queue', []);

        } catch (\UnexpectedValueException $e) {
            $dbTransaction->rollBack();
        } catch (\Throwable $e) {
            \Yii::error(['message' => $e->getMessage(), 'stack' => $e->getTrace()]);
            $dbTransaction->rollBack();
        }

        return $action;
    }
}