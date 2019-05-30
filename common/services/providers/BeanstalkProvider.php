<?php
namespace common\services\providers;

use Yii;
use common\services\providers\QueueProvider;
use udokmeci\yii2beanstalk\Beanstalk;
use yii\base\Component;

class BeanstalkProvider extends Component implements QueueProvider
{
    /* @var Beanstalk $beanstalk */
    public $beanstalk;

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->beanstalk = Yii::$app->get($this->beanstalk);

        parent::init();
    }

    public function add($queueId, $data)
    {
        $this->beanstalk->putInTube($queueId, $data);
    }
}
