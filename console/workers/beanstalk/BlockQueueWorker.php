<?php

namespace console\workers\beanstalk;

use common\models\Variable;
use Pheanstalk\Job;
use yii\base\InvalidValueException;

class BlockQueueWorker extends \console\workers\beanstalk\BeanstalkController
{
    public function listenTubes()
    {
        return ['block-queue'];
    }

    /**
     * @param Job $job
     * @return string
     * @throws \yii\db\Exception
     */
    public function actionBlockQueue(Job $job)
    {
        // get last parsed block
        $dbTransaction = \Yii::$app->db->beginTransaction();

        try {
            /* @var Variable $variable */
            $variable = Variable::findBySql('SELECT * FROM {{%variable}} WHERE key = :key FOR UPDATE',
                [
                    ':key' => Variable::KEY_LAST_PARSED_BLOCK
                ])->one();

            if (!$variable) {
                // trying to create and then lock again
                $newlyCreatedOne = Variable::createKey(Variable::KEY_LAST_PARSED_BLOCK);
                $dbTransaction->commit();

                $dbTransaction = \Yii::$app->db->beginTransaction();
                $variable = Variable::findBySql('SELECT * FROM {{%variable}} WHERE key = :key FOR UPDATE',
                    [
                        ':key' => $newlyCreatedOne->key
                    ])->one();
            }

            if (!$variable) {
                throw new InvalidValueException("Failed to obtain or create variable `" . Variable::KEY_LAST_PARSED_BLOCK . "`");
            }

            $queuedVariable = Variable::findOne(['key' => Variable::KEY_LAST_QUEUED_BLOCK]);
            if (!$queuedVariable) {
                throw new InvalidValueException("Failed to obtain variable `" . Variable::KEY_LAST_QUEUED_BLOCK . "`");
            }


            if (!$variable->value) {
                $variable->value = $queuedVariable->value - 1;
                $variable->save();
            }
            $dbTransaction->commit();

            if ((int)$queuedVariable->value > (int)$variable->value) {
                \Yii::$app->queue->add('block-parser', $variable->value + 1);
            }
        } catch (\Throwable $e) {
            \Yii::error(['message' => $e->getMessage(), 'stack' => $e->getTrace()]);
            $dbTransaction->rollBack();
        }


        return self::DELETE;
    }
}