<?php

namespace Sunra\RedisQueueBundle\RedisQueue\Worker;

use Sunra\RedisQueueBundle\RedisQueue;


class State
{
	const REDIS_KEY_WORKER_STATE_BASE = 'state:worker';
	
	private $REDIS_KEY_WORKER_STATE;
	private $config;
	private $redis;
	private $error = array('status' => false, 'text' => '');



    public function get() 	
	{
		if($this->error['status'] === true) 
		{
			$state = array();
			
			$state['active'] = false;
			$state['state_text'] = 'ERROR';			
			$state['logical_state'] = 'none'; // ?
			$state['fields']['error'] = $this->error['text'];
			
			
			return $state; 
		}
		
		$state = $this->redis->hgetall($this->REDIS_KEY_WORKER_STATE);
		
		if (isset($state['PID']) && (posix_getsid($state['PID']) !== false) ) {
			$state['active'] = true;
			$state['state_text'] = 'ON';			
			$state['logical_state'] = 'runing';
			$state['fields']['PID'] = $state['PID'];
			
			if(isset($state['TIME_CUR']) && isset($state['TIME_START'])) {
			    $state['fields']['UPTIME'] = $state['TIME_CUR'] - $state['TIME_START'];
			}
			
			if(isset($state['ITERATIONS']))
			{
			    $state['fields']['ITERATIONS'] = $state['ITERATIONS'];
			}

            if(isset($state['COUNTER_MESSAGES_READED']))
            {
                $state['fields']['MESSAGES_READED'] = $state['COUNTER_MESSAGES_READED'];

                $state['fields']['SPEED_MESAGES_IN_SECOND'] = round($state['fields']['MESSAGES_READED'] / $state['fields']['UPTIME'],0,PHP_ROUND_HALF_UP ) ;
            }


		} 
		elseif(isset($state['STOPPED'])) {
			$state['logical_state'] = 'stopped';
			$state['active'] = false;
			$state['state_text'] = 'OFF';
		}
		else {
			$state['logical_state'] = 'none';
		    $state['active'] = false;
			$state['state_text'] = 'OFF';
		}		
		
	    return $state;
	}
	
	public function setError($errorText) 
	{
		$this->error['status'] = true;
		$this->error['text'] = $errorText;
		
		return $this;
	}
	
	public function isActive() 
	{
		$state = $this->get();
		
		return $state['active'];
	}
	
	
	public function isStopped() 
	{
		$state = $this->get();
		
		return isset($state['STOPPED']);
	} 

	
	public function getIterations() {
		$state = $this->get();		
		return $state['ITERATIONS'];
	}
		
	
	public function setPid() 
	{
		$this->redis->hset($this->REDIS_KEY_WORKER_STATE, 'PID', getmypid ());
	}


	public function getPid() 
	{
		$state = $this->get();
		
		return $state['PID'];
	}

	
	public function setTimeStart() 
	{
		$this->redis->hset($this->REDIS_KEY_WORKER_STATE, 'TIME_START', time ());
	}


	public function setTimeCur() 
	{
		$this->redis->hset($this->REDIS_KEY_WORKER_STATE, 'TIME_CUR', time ());
	}
	
    
	public function zeroIterations() 
	{
		$this->redis->hset($this->REDIS_KEY_WORKER_STATE, 'ITERATIONS', 0);
	}
    
	
    public function incIterations() 
	{
		return $this->redis->hincrby($this->REDIS_KEY_WORKER_STATE, 'ITERATIONS', 1);
		
	}


    public function incCounter($counter_name)
    {
        return $this->redis->hincrby($this->REDIS_KEY_WORKER_STATE, 'COUNTER_'.$counter_name, 1);

    }


    public function setStopped()
	{
		$this->redis->hset($this->REDIS_KEY_WORKER_STATE, 'STOPPED', 'Y');
		
	}


	public function delStopped() 
	{
		$this->redis->hdel($this->REDIS_KEY_WORKER_STATE, 'STOPPED');
		
	}
	
			
	public function del() {		
		$this->redis->del($this->REDIS_KEY_WORKER_STATE);
	}
	
	
	public function getHuman() {
		$state = $this->get();
		if (isset($state['fields'])) 
		{
			if (isset($state['fields']['UPTIME'])) 
			{
		        $state['fields']['UPTIME'] = RedisQueue\Utils::secondsToHuman( $state['fields']['UPTIME']);
			}
		}
		
		return $state;		
	}


    public function __construct($config, $redis) 
	{		
	    $this->config = $config;
	    $this->redis = $redis;

		
		$this->REDIS_KEY_WORKER_STATE = self::REDIS_KEY_WORKER_STATE_BASE. ':'.$this->config['name'].'.'.$this->config['#'];
	}
	
}

