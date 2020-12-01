<?php

namespace Sunra\RedisQueueBundle\RedisQueue\Supervisor;

use Predis;

use Sunra\RedisQueueBundle\RedisQueue;

/*

3 logical state of SV:

- runing
- stopped
- none


3 logical signals of SV:

- start
- stop
- cron_start  



if runing 
   start is imposible
   stop is posible
   cron_start is imposible
   
if stopped
   start is posible
   stop is imposible
   cron_start is imposible
   
if none
   start is posible
   cron_start is posible
   stop is imposible
   

   
start
   del STOPPED
   
stop
   set STOPPED

 

*/

class State
{
    const REDIS_KEY_SUPERVISOR_STATE = 'state:supervisor';
	
	/** @var Predis\Client $redis */
	private $redis;
    private $config;
	
	
	function __construct($redis, $config) 
	{
	    $this->redis = $redis;
		$this->config = $config;
	}


    public function get() 
	{
		$state = $this->redis->hgetall(self::REDIS_KEY_SUPERVISOR_STATE);
		
		if (isset($state['PID']) && (posix_getsid($state['PID']) !== false) ) {
			$state['active'] = true;
			$state['state_text'] = 'ON';			
			$state['logical_state'] = 'runing';
			$state['fields']['PID'] = $state['PID'];
			
			if(isset($state['TIME_CUR']) && isset($state['TIME_START'])) 
			{
			    $state['fields']['UPTIME'] = $state['TIME_CUR'] - $state['TIME_START'];
			}
			
			if(isset($state['ITERATIONS']))
			{
			    $state['fields']['ITERATIONS'] = $state['ITERATIONS'];
			}

            if(isset($state['GLOBAL_MESSAGES_COUNTER']))
            {
                $state['fields']['GLOBAL_MESSAGES_COUNTER'] = $state['GLOBAL_MESSAGES_COUNTER'];
            }
			
			if (isset($state['config_saved_crc'])) 
			{				
				//var_dump($this->supervisor); die();
				$state['config_changed'] = crc32(serialize($this->config)) != $state['config_saved_crc'];
			}
			
			if (isset($state['config_saved'])) 
			{
			    $state['config_saved'] = unserialize($state['config_saved']);
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


    public function setPid()
	{
		return $this->redis->hset(self::REDIS_KEY_SUPERVISOR_STATE, 'PID', getmypid ());
	}

	public function getPid() 
	{
		$state = $this->get();
		
		return $state['PID'];
	}

	
	public function setTimeStart() 
	{
		$this->redis->hset(self::REDIS_KEY_SUPERVISOR_STATE, 'TIME_START', time ());
	}


	public function setTimeCur() 
	{
		$this->redis->hset(self::REDIS_KEY_SUPERVISOR_STATE, 'TIME_CUR', time ());
	}
	
	public function setConfig() 
	{
		$config_ser = serialize($this->config);
		$config_ser_crc = crc32($config_ser);
		
		$this->redis->hset(self::REDIS_KEY_SUPERVISOR_STATE, 'config_saved', $config_ser);
		$this->redis->hset(self::REDIS_KEY_SUPERVISOR_STATE, 'config_saved_crc', $config_ser_crc);
	}

	
	public function incIterations() 
	{
		return $this->redis->hincrby(self::REDIS_KEY_SUPERVISOR_STATE, 'ITERATIONS', 1);
	}


	public function getIterations()
    {

    }


    public function clearIterations() {
        $this->redis->hset(self::REDIS_KEY_SUPERVISOR_STATE, 'ITERATIONS', 0);
    }



    public function incCounter($counter_name)
    {
        return $this->redis->hincrby(self::REDIS_KEY_SUPERVISOR_STATE, 'COUNTER_'.$counter_name, 1);
    }


    public function getGlobalMessagesCounter()
    {
        return $this->redis->hget(self::REDIS_KEY_SUPERVISOR_STATE, 'GLOBAL_MESSAGES_COUNTER');
    }

	
	public function setStopped() 
	{
		$this->redis->hset(self::REDIS_KEY_SUPERVISOR_STATE, 'STOPPED', 'Y');		
	}


	public function delStopped() 
	{
		$this->redis->hdel(self::REDIS_KEY_SUPERVISOR_STATE, 'STOPPED');		
	}



			
	public function del()
	{		
		$this->redis->del(self::REDIS_KEY_SUPERVISOR_STATE);
	}
	
	
	public function getHuman()
	{
		$state = $this->get();
		if (isset($state['fields']['UPTIME'])) 
		{
		    $state['fields']['UPTIME'] = RedisQueue\Utils::secondsToHuman( $state['fields']['UPTIME']);
		}
		
		return $state;		
	}
	
	
	
}