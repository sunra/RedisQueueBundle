<?php

namespace Sunra\RedisQueueBundle\RedisQueue;

use Sunra\RedisQueueBundle\RedisQueue\Message\State;
use Predis\Collection\Iterator;
use Symfony\Component\Process\Process;


class Queue {
	private $config;	
    private $redis;
    private $kernel;
    private $freezed = false;


    public function freeze()
    {
        $this->freezed = true;
    }
    
    public function isFreezed()
    {
        return $this->freezed;
    }
    
    public function unfreeze()
    {
        $this->freezed = false;
    }
    
    
   /**
    * @param string 
    * @return int
    */
   	public function getSize($state = Message\State::PUBLISHED)
    {                
        return $this->redis->SCARD(
            $this->getRedisKeyName($state)
        );        
    }
    
    
   /**
    * @param string $state
    * @param bool   $wo_state
    *
    * @return string
    */   
    public function getRedisKeyName($state='', $wo_state = false)
    {
        if(!$wo_state)
        {
            Message\State::check($state);
        }

        return 'queue:' . $this->getQueueName() . ':' . $state;
    }
    
    
    public function getRedis()
    {
        return $this->redis;
    }
    
    
    public function getQueueName()
    {
        return $this->config['name'];
    }
    

    /**
     * Return sizes of all queue states
     *      
     * @return array hash
     */    
    public function getSizes()
    {
        $sizes = array();
                
        $i=0;
        foreach(Message\State::listAll() as $state) 
        {
            $size=$this->getSize($state);

            $sizes[] = array(
                'order'=>$i++,
                'state'=>$state, 
                'size'=>$size, 
                'show' => $state != Message\State::CREATED,
                'enabled_RESTART' => 
                    $size>0 && 
                    $state != Message\State::PUBLISHED &&
                    $state != Message\State::PROCESSED,
                'enabled_VIEW' => 
                    $size>0,
                'enabled_CLEAR' => 
                    $size>0,        
            ); 
        }
        
        return $sizes;        
    }
    
   
    /**
     * Delete messages with state from this queue
     *
     * @param string
     * @return boolean is operation succesfull
     */    
   	public function clear($state)
    {
        Message\State::check($state);
                
        $set_name = $this->getRedisKeyName($state);
            
        $result = true;
        
        foreach (new Iterator\SetKey($this->redis, $set_name) as $member) {
            $this->redis->DEL($member);

            /*if (!$this->redis->DEL($member))
            {
                throw new \Exception('Error delete message, '.$member);
            }*/
        }
            
        if(!$this->redis->DEL( $set_name ))
        {
            throw new \Exception('Error delete message state, '.$set_name); 
        }
                
        return true;
    }


    /**
     * Delete messages with state from this queue
     * Via async console command
     *
     * @param string
     * @return boolean is operation succesfull always
     */
    public function clearAsync($state)
    {
        Message\State::check($state);

        $process = new Process($this->kernel->getRootDir().'/../bin/console redis-queue:queue:clear '.$this->getQueueName().' '.$state);
        $process->start();

        return true;
    }


    /**
     * Delete all messages with any state from this queue
     *
     * @return boolean
     */    
   	public function clearAll()
    {
        $result = true;
        
        foreach(Message\State::listAll() as $state) 
        {
            $result1 = $this->clear($state);            
            $result &= $result1;
        }
        
        return $result;        
    }
    
    

    

    /**
     * Read message from PUBLISHED
     * Message state changed to INPROCESS
     *
     * @param string 
     * @return Message|false
     */
	public function read()
    {        
        $message_id = $this->redis->SRANDMEMBER(
            $this->getRedisKeyName(Message\State::PUBLISHED)
        );
        
        if(!$message_id) return false; // Empty queue
        
        $message = new Message($this, $message_id);

        if(!$message->state->isPublished()) { // strange - another worker picked up this message already
            return false;
        }
             
        $message->inprocess();        
        
        return $message;   
    }
    
    
    /**
 * Read $count messages from $fromState
 * Message state not changed
 *
 * @param $fromState
 * @param int $count Num of messages to read (return count - may be less)
 * @param int $offset Num mesages to skip to needed offset. Zero based
 * @return array of RedisQueue\Message
 */
    public function show($fromState, $count, $offset=0)
    {
        $set_name = $this->getRedisKeyName($fromState);
        $position = 0;
        $messages = array();

        //var_dump($count, $offset);//die();

        foreach (new Iterator\SetKey($this->redis, $set_name,'', $offset+$count) as $member)
        {
            $position++;

            if($position < $offset) continue;

            $messages[] = $this->getMessage($member);

            if (count($messages) >= $count) break;
        }

        return $messages;
    }


    /**
     * Read $count messages from $fromState
     * Message state not changed
     *
     * @param $fromState
     * @param int $count Num of messages to read (return count - may be less)
     * @param int $offset Num mesages to skip to needed offset. Zero based
     * @return array of RedisQueue\Message
     */
    public function showInfo($fromState, $count, $offset=0)
    {
        $set_name = $this->getRedisKeyName($fromState);
        $position = 0;
        $messages_info = array();

        //var_dump($count, $offset);//die();

        foreach (new Iterator\SetKey($this->redis, $set_name,'', $offset+$count) as $member)
        {
            $position++;

            if($position < $offset) continue;

            $messages_info[] = $this->getMessage($member)->getInfo();

            if (count($messages_info) >= $count) break;
        }

        return $messages_info;
    }
    
    /**
     * Get message from queue by ID
     * Message state not changed
     *
     * @param string
     * @return RedisQueue\Message
     */
	public function getMessage($id)
    {        
        $message = new Message($this, $id);   
        
        return $message;   
    }

    
    /**
     * Create new message in queue
     * State sets to PUBLISHED
     *
     * @param string $payload
     * @return RedisQueue\Message
     */
	public function publish($payload)
    {        
        $message = new Message($this);        
        
        $message->setPayload($payload);
        $message->publish();
        
        return $message;          
    }    
    
	
    public function getConfig()
    {
        return $this->config;
    }
    


    /*
     * $state_where_search - supported CREATED and INPROCESS
     **/
    public function droppedMessagesDetection($state_where_search, $probability=100)
    {
        if (!in_array($state_where_search, array(Message\State::CREATED, Message\State::INPROCESS)))
        {
            throw new \Exception('Supported CREATED and INPROCESS states only');
        }

        if(!$this->probablyDo($probability)) return false;

        if($this->getSize($state_where_search) == 0)
        {
            return false;
        }

        $set_name = $this->getRedisKeyName($state_where_search);

        $found = false;
        foreach (new Iterator\SetKey($this->redis, $set_name) as $member) {
            $message = $this->getMessage($member);

            $ts = $message->state->getTimestamp($state_where_search);

            $ts_delta = time()-$ts;

            if($ts_delta > $this->config['timeout_'.$state_where_search])
            {
                //echo "ts_INPROCESS:$ts_INPROCESS; ".$this->config['timeout_INPROCESS']."; \n\n ";
                //echo $message->getId()." message will be dropped \n\n";
                $message->dropped("Dropped from $state_where_search, with timeout $ts_delta (ts ".$ts."; max ".$this->config['timeout_'.$state_where_search].")");
                $found = true;
            }
        }

        return $found;
    }


    /**
     * Cut size of "X" queue state to max_messages_"X"
     */
    function trim($state, $probability=100)
    {
        Message\State::check($state);

        if(!$this->probablyDo($probability)) return false;


        $set_size = $this->getSize($state);

        $oversize = $set_size - $this->config['max_messages_'.$state];
        //$offset = $set_size - $oversize;

        //echo "oversize $oversize\n";

        if($oversize <= 0) return false;

        $oversize_messages = $this->show($state, $oversize /*, $offset*/);

        /** @var Message $oversize_message */
        foreach($oversize_messages as $oversize_message)
        {
            //var_dump($oversize_message);
            //echo "oversized message: ".$oversize_message->getId()."\n";

            $oversize_message->delete();
        }
    }


    /* 
     * Execute func with $probability 
     */
    function probablyDo($probability=100)
    {
        if ($probability < 100) 
        {
            $chance = rand(0,100);
            if ($chance > $probability) return false;   
        }
     
        return true;    
    }


    
    
    /*
     * Move all messages with $fromState to toState
     */
    function moveState($fromState, $toState)
    {
        Message\State::check($fromState);
        Message\State::check($toState);
        
        $fromStateSetName = $this->getRedisKeyName($fromState);
        
        foreach (new Iterator\SetKey($this->redis, $fromStateSetName) as $member) 
        {
            $message = $this->getMessage($member);
            $message->state->set($toState);
        }        
        
        return true;        
    }
    
    
    /**
     *
     * @return boolean
     */    
   	public function republish($state)
    {        
        return $this->moveState($state, Message\State::PUBLISHED);
    }    
    
    
    
	function __construct($config, $redis, $kernel) {
       
		$this->config = $config;
		$this->redis = $redis;
        $this->kernel = $kernel;
	}
}