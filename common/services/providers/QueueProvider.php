<?php
namespace common\services\providers;

interface QueueProvider
{
    public function add($queueId, $data);
}