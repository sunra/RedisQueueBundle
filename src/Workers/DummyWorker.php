<?php
namespace Sunra\RedisQueueBundle\Workers;

use Sunra\RedisQueueBundle\RedisQueue;





class DummyWorker extends RedisQueue\AbstractWorker {
	
	public function run( $payload, \Sunra\RedisQueueBundle\RedisQueue\Message $message )
	{
		//$message->payload;
		
		//$message->ok();
		//$message->bad();
		
	}
	
	public function keepalive() 
	{
	}
	
	
	public function __construct()
	{
	}
	
	
}