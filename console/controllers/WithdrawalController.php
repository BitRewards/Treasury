<?php
namespace console\controllers;

use common\helpers\ThrowableModelErrors;
use common\models\Withdrawal;
use yii\base\InvalidArgumentException;
use yii\base\InvalidValueException;
use yii\console\Controller;

class WithdrawalController extends Controller
{
    public function actionQueuePending()
    {
        $query = Withdrawal::find()
            ->where(['and', ['status' => Withdrawal::STATUS_PROCESSED], ['<', 'updated_at', time() - Withdrawal::CHECK_STATUS_DELAY]])
            ->andWhere(['or', ['next_attempt_at' => null], ['<', 'next_attempt_at', time()]]);

        foreach ($query->batch() as $withdrawalBatch) {
            foreach ($withdrawalBatch as $withdrawal) {
                \Yii::$app->queue->add('transaction-parser', $withdrawal->attributes);
            }
        }
    }

    /**
     * @throws \yii\db\Exception
     */
    public function actionQueueNotProcessed()
    {
        /*$query = Withdrawal::findBySql(
            'SELECT * FROM {{%withdrawal}} WHERE status = :status AND next_attempt_at < :timestamp FOR UPDATE', [
            ':status' => Withdrawal::STATUS_INVALID_BLOCKCHAIN_RESPONSE,
            ':timestamp' => time()
        ]);*/

        $query = Withdrawal::find()
            ->where(['status' => [Withdrawal::STATUS_INVALID_BLOCKCHAIN_RESPONSE, Withdrawal::STATUS_NOT_PROCESSED]])
            ->andWhere(['or', ['next_attempt_at' => null], ['<', 'next_attempt_at', time()]]);

        foreach ($query->batch() as $withdrawalBatch) {
            foreach ($withdrawalBatch as $withdrawalFromBatch) {
                $transaction = \Yii::$app->db->beginTransaction();
                try {
                    // lock for update
                    // make sure we are locking one record
                    $withdrawal = Withdrawal::findBySql(
                        'SELECT * FROM {{%withdrawal}} WHERE id = :id AND (status = :status OR status = :noproc_status) AND (next_attempt_at IS NULL OR next_attempt_at < :timestamp) FOR UPDATE', [
                        ':id' => $withdrawalFromBatch->id,
                        ':status' => Withdrawal::STATUS_INVALID_BLOCKCHAIN_RESPONSE,
                        ':noproc_status' => Withdrawal::STATUS_NOT_PROCESSED,
                        ':timestamp' => time()
                    ])->one();
                    if (!$withdrawal) {
                        throw new InvalidArgumentException("Withdrawal not found");
                    }

                    $withdrawal->status = Withdrawal::STATUS_WAITING_RETRY;
                    if (!$withdrawal->save()) {
                        throw new InvalidValueException(ThrowableModelErrors::jsonMessage($withdrawal));
                    }
                    $transaction->commit();

                    \Yii::$app->queue->add('withdrawal', $withdrawal->attributes);
                } catch (\Throwable $e) {
                    $transaction->rollBack();
                }
            }
        }
    }
}
