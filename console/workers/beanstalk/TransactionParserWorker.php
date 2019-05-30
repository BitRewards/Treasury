<?php

namespace console\workers\beanstalk;

use common\exceptions\InvalidBlockchainResponse;
use common\helpers\ThrowableModelErrors;
use common\helpers\WeiHelper;
use common\models\Withdrawal;
use Pheanstalk\Job;
use yii\base\InvalidArgumentException;
use yii\base\InvalidValueException;
use yii\db\Exception;

class TransactionParserWorker extends \console\workers\beanstalk\BeanstalkController
{
    public function listenTubes()
    {
        return ['transaction-parser'];
    }

    /**
     * @param Job $job
     * @return string
     * @throws \yii\db\Exception
     */
    public function actionTransactionParser(Job $job)
    {
        /* @var \stdClass $data */
        $data = $job->getData();

        $action = self::DELETE;

        $dbTransaction = \Yii::$app->db->beginTransaction();
        try {
            $withdrawal = Withdrawal::findBySql('SELECT * FROM {{%withdrawal}} WHERE id = :id AND status = :status FOR UPDATE',
                [
                    ':id' => $data->id,
                    ':status' => Withdrawal::STATUS_PROCESSED,
                ])->one();

            if (!$withdrawal) {
                throw new InvalidArgumentException('Processed withdrawal not found');
            }

            $tx = \Yii::$app->eth->getTransactionByHash($withdrawal->tx_id);
            if (empty($tx['blockNumber'])) {
                throw new InvalidBlockchainResponse("No block number");
            }

            $withdrawal->block_number = $tx['blockNumber'];
            $withdrawal->eth_fee = WeiHelper::fromWei($tx['eth_fee']);
            $withdrawal->gas_limit = WeiHelper::fromWei($tx['gas_limit']);
            $withdrawal->gas_price = WeiHelper::fromWei($tx['gas_price']);

            $withdrawal->status = Withdrawal::STATUS_CONFIRMED;
            if (!$withdrawal->save()) {
                throw new InvalidValueException(ThrowableModelErrors::jsonMessage($withdrawal));
            }

            $dbTransaction->commit();
        } catch (InvalidBlockchainResponse $e) {
            if ($withdrawal instanceof Withdrawal) {
                $withdrawal->blockchain_response = $e->getMessage();
                $withdrawal->attempts_count++;
                if ($withdrawal->attempts_count < Withdrawal::MAX_WITHDRAWAL_ATTEMPTS) {
                    $withdrawal->next_attempt_at = Withdrawal::getNextAttemptTimestamp($withdrawal->attempts_count);
                    $withdrawal->save();
                } else {
                    $withdrawal->status = Withdrawal::STATUS_FAILED;
                    $withdrawal->save();
                }
                \Yii::error(['message' => "Withdrawal {$withdrawal->id} processing error: " . $e->getMessage(), 'object' => $withdrawal->attributes], InvalidBlockchainResponse::class);
            } else {
                \Yii::error($e->getMessage(), InvalidBlockchainResponse::class);
            }

            $dbTransaction->commit();
        } catch (\Throwable $e) {
            \Yii::error($e->getMessage());

            $dbTransaction->rollBack();
        }

        return $action;
    }
}