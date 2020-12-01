<?php

namespace Sunra\RedisQueueBundle\RedisQueue;

abstract class AbstractWorker {
    /*
     * Do worker job
     * @param string $payload
     * @param Sunra\RedisQueueBundle\RedisQueue\Message $message
      
     * if returns true - message asumed PROCESSED
     * if FALSE - FAILED
     */
	abstract public function run($payload, Message $message);

}