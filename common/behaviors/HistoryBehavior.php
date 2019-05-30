<?php

namespace common\behaviors;

use Yii;
use common\models\History;
use yii\base\Behavior;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;

class HistoryBehavior extends Behavior
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
        if ( $owner instanceof ActiveRecord === false) {
            throw new InvalidConfigException('ActiveRecord expected');
        }

        /* @var ActiveRecord $owner */
        $this->state = $owner->attributes;
        parent::attach($owner);
    }

    public function events()
    {
        return [
            BaseActiveRecord::EVENT_AFTER_INSERT => 'onInsert',
            BaseActiveRecord::EVENT_AFTER_DELETE => 'onDelete',
            BaseActiveRecord::EVENT_AFTER_UPDATE => 'onUpdate',
        ];
    }

    public function onInsert($event)
    {
        $this->log(History::EVENT_RECORD_INSERTED);
    }

    public function onUpdate($event)
    {
        $owner = $this->owner;

        $changedAttributes = $event->changedAttributes;

        // do not log timestamps changes
        unset($changedAttributes['created_at'], $changedAttributes['updated_at']);
        if (empty($changedAttributes)) {
            return;
        }

        $attrKeys = array_keys($changedAttributes);
        $newAttributes = array_filter($owner->attributes, function($item, $key) use ($attrKeys) {
            return in_array($key, $attrKeys, true);
        }, ARRAY_FILTER_USE_BOTH);

        $data = [
            'before' => $changedAttributes,
            'after' => $newAttributes
        ];

        $this->log(History::EVENT_RECORD_UPDATED, $data);
    }

    public function onDelete($event)
    {
        $this->log(History::EVENT_RECORD_DELETED);
    }

    protected function log($type, $data = null)
    {
        try {
            $h = new History();
            $h->type = $type;

            /**
             * @var ActiveRecord $owner
             */
            $owner = $this->owner;

            $h->classname = get_class($owner);
            $h->model_id = $owner->id;

            $h->data = $data;

            $h->save();
        } catch (\Throwable $e) {
            // log invalid history
            //throw $e;
        }
    }
}