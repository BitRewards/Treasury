<?php
namespace common\services;

use common\services\providers\QueueProvider;
use yii\base\Component;

class QueueService extends Component
{
    /* @var QueueProvider $provider */
    public $provider;

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->provider = \Yii::createObject($this->provider);
        parent::init();
    }

    public function add($queueId, $data)
    {
        $this->provider->add($queueId, $data);
    }
}