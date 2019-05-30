<?php

namespace common\behaviors;

use Yii;
use common\models\Withdrawal;
use yii\base\Behavior;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\db\BaseActiveRecord;

class WithdrawalStatusBehavior extends Behavior
{
    /**
     * @var array State of the object before update or insert
     */
    public $state;

    /**
     * Saving current state of the object to $state
     *
     * @param \yii\base\Component $owner
     * @throws InvalidConfigException
     */
    public function attach($owner)
    {
        if ( $owner instanceof Withdrawal === false) {
            throw new InvalidConfigException('Withdrawal expected');
        }

        /* @var Withdrawal $owner */
        $this->state = $owner->attributes;
        parent::attach($owner);
    }

    public function events()
    {
        return [
            BaseActiveRecord::EVENT_AFTER_INSERT => 'onAfterInsert',
            BaseActiveRecord::EVENT_AFTER_UPDATE => 'onAfterUpdate',
        ];
    }

    /**
     * @param $event
     * @throws InvalidConfigException
     */
    public function onAfterInsert($event)
    {
        /* @var Withdrawal $owner */
        $owner = $this->owner;

        if (empty($owner->status) || $owner->status === Withdrawal::STATUS_NOT_PROCESSED) {

            Yii::$app->queue->add('withdrawal', $owner->attributes);
        }
    }

    /**
     * @param $event
     * @throws \Throwable
     * @throws \common\exceptions\ValidationException
     * @throws \yii\db\Exception
     */
    public function onAfterUpdate($event)
    {
        /** @var Withdrawal $owner */
        $owner = $this->owner;

        $changedAttributes = $event->changedAttributes;

        if (array_key_exists('status', $changedAttributes) && $owner->status === Withdrawal::STATUS_PROCESSED) {
            $owner->attempts_count = 0;
            $owner->next_attempt_at = null;
            $owner->save();
        }

        // send callback when withdrawal is confirmed or failed
        if (array_key_exists('status', $changedAttributes) && in_array($owner->status, [Withdrawal::STATUS_CONFIRMED, Withdrawal::STATUS_FAILED])) {
            // we should lock record

            if ($transaction = Yii::$app->db->getTransaction()) {
                $owner->createAndSetCallback();
            } else {
                $transaction = Yii::$app->db->beginTransaction();
                try {

                    /* @var Withdrawal $withdrawal */
                    $withdrawal = Withdrawal::findBySql('SELECT * FROM {{%withdrawal}} WHERE id = :id FOR UPDATE',
                        [
                            ':id' => $owner->id,
                        ])->one();

                    if (!$withdrawal) {
                        throw new InvalidArgumentException('Initiated withdrawal not found');
                    }

                    $withdrawal->createAndSetCallback();
                    $transaction->commit();
                } catch (\Throwable $e) {
                    $transaction->rollBack();
                    // throw further
                    throw $e;
                }
            }
        }
    }
}
