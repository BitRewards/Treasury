<?php
namespace console\workers\beanstalk;

use udokmeci\yii2beanstalk\BeanstalkController as BaseBeanstalkController;

class BeanstalkController extends BaseBeanstalkController
{
    public function mysqlSessionTimeout()
    {
        // do nothing
    }
}
