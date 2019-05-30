<?php

namespace console\workers\beanstalk;

use common\exceptions\InvalidBlockchainResponse;
use common\exceptions\NotEnoughBalanceException;
use common\exceptions\NotImplementedException;
use common\helpers\ThrowableModelErrors;
use common\helpers\WeiHelper;
use common\models\Withdrawal;
use Pheanstalk\Job;
use yii\base\InvalidArgumentException;
use yii\base\InvalidValueException;
use yii\helpers\Json;

class WithdrawalWorker extends \console\workers\beanstalk\BeanstalkController
{
    public function listenTubes()
    {
        return ['withdrawal'];
    }

    /**
     * @param Job $job
     * @return string
     */
    public function actionWithdrawal(Job $job)
    {
        /* @var \stdClass $data */
        $data = $job->getData();

        $eth = \Yii::$app->eth;

        /**
         * default beanstalk action
         */
        $action = self::DELAY;

        $transaction = \Yii::$app->db->beginTransaction();
        $withdrawal = null;
        try {
            /* @var Withdrawal $withdrawal */
            $withdrawal = Withdrawal::findBySql('SELECT * FROM {{%withdrawal}} WHERE id = :id AND (status = :status OR status = :retry_status) FOR UPDATE',
                [
                    ':id' => $data->id,
                    ':status' => Withdrawal::STATUS_NOT_PROCESSED,
                    ':retry_status' => Withdrawal::STATUS_WAITING_RETRY
                ])->one();

            if (!$withdrawal) {
                throw new InvalidArgumentException('Pending withdrawal not found');
            }

            $withdrawal->status = Withdrawal::STATUS_INITIATED;
            if (!$withdrawal->save()) {
                throw new InvalidValueException(ThrowableModelErrors::jsonMessage($withdrawal));
            }

            // make sure that we save the state before executing blockchain operations
            $transaction->commit();
            // once again retrieve record with extra lock
            $transaction = \Yii::$app->db->beginTransaction();


            $withdrawal = Withdrawal::findBySql('SELECT * FROM {{%withdrawal}} WHERE id = :id AND status = :status FOR UPDATE',
                [
                    ':id' => $data->id,
                    ':status' => Withdrawal::STATUS_INITIATED
                ])->one();

            if (!$withdrawal) {
                throw new InvalidArgumentException('Initiated withdrawal not found');
            }

            if (!$withdrawal->checkUserBalance()) {
                throw new NotEnoughBalanceException('not enough balance');
            }

            $tx_id = null;
            switch ($withdrawal->currency) {
                case Withdrawal::CURRENCY_ETH:
                    $tx_id = $eth->sendEther($withdrawal->user_id, $withdrawal->dest_address,
                        WeiHelper::inWei($withdrawal->amount));
                    break;

                case Withdrawal::CURRENCY_BIT:
                    $tx_id = $eth->sendToken($withdrawal->user_id, $withdrawal->dest_address,
                        WeiHelper::inWei($withdrawal->amount));
                    break;

                default:
                    throw new NotImplementedException("Unrecognized currency: " . $withdrawal->currency);
                    break;
            }

            if (empty($tx_id)) {
                throw new InvalidBlockchainResponse('invalid tx_id');
            }

            $withdrawal->tx_id = $tx_id;
            $withdrawal->status = Withdrawal::STATUS_PROCESSED;
            if (!$withdrawal->save()) {
                throw new InvalidValueException(ThrowableModelErrors::jsonMessage($withdrawal));
            }

            $action = self::DELETE;
        } catch (InvalidBlockchainResponse $e) {

            /* @var Withdrawal $withdrawal */
            if ($withdrawal instanceof Withdrawal) {

                if ($e instanceof NotEnoughBalanceException) {
                    if (!$withdrawal->attempts_count) {
                        $withdrawal->createLowBalanceCallback();
                    }
                }


                $withdrawal->blockchain_response = $e->getMessage();

                $withdrawal->attempts_count++;

                if ($withdrawal->attempts_count < Withdrawal::MAX_WITHDRAWAL_ATTEMPTS) {
                    $withdrawal->status = Withdrawal::STATUS_INVALID_BLOCKCHAIN_RESPONSE;
                    $withdrawal->next_attempt_at = Withdrawal::getNextAttemptTimestamp($withdrawal->attempts_count);
                    $withdrawal->save();
                } else {
                    $withdrawal->status = Withdrawal::STATUS_FAILED;
                    $withdrawal->save();
                }
                \Yii::error(['message' => "Withdrawal {$withdrawal->id}: " . $e->getMessage(), 'object' => $withdrawal->attributes], InvalidBlockchainResponse::class);
            } else {
                \Yii::error($e->getMessage(), InvalidBlockchainResponse::class);
            }
            $action = self::DELETE;
        } catch (\Throwable $e) {
            if ($withdrawal instanceof Withdrawal) {
                $withdrawal->status = Withdrawal::STATUS_FAILED;
                $withdrawal->save();
                \Yii::error(['message' => $e->getMessage(), 'object' => $withdrawal->attributes, 'stack' => $e->getTraceAsString()]);
            } else {
                \Yii::error($e->getMessage());
            }
            $action = self::BURY;
        }

        /* commit here to release lock and update transaction status whatever it was set to  */
        $transaction->commit();
        return $action;
    }
}
