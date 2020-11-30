<?php

namespace Sunra\RedisQueueBundle\RedisQueue;

//use Sunra\RedisQueueBundle\RedisQueue;

class Message {
    private $id;
 	private $queue;	
    private $redis;

    /** @var Message\State $state */
    public $state;

    /*
     * Get all public fields in human readable format
     *
     * */
    public function getInfo()
    {
        $fields = array();

        $fields['id'] = $this->getId();
        $fields['payload'] = $this->getPayload();
        $fields['state'] = $this->state->get();
        $fields['queue'] = $this->queue->getQueueName();
        $fields['timeline'] = $this->getTimeline();

        //if($this->loop_count > 0)
        //{
        //    $fields['loop_count'] = $this->loop_count;
        //}

        return $fields;
    }


    private function getTimeline()
    {
        $timeline = array();
        foreach( Message\State::listAll() as $n=>$state )
        {
            $var_name_ts = Message\State::getStateTimestampFieldName($state);
            $var_name_comment = Message\State::getStateCommentFieldName($state);

            if( isset( $this->$var_name_ts ) )
            {
                $comment = '';
                if(isset($this->$var_name_comment))
                {
                    $comment = $this->$var_name_comment;
                }

                $timeline[] = array(
                    'state' => $state,
                    'date' => Utils::dateToHuman($this->$var_name_ts),
                    'sort_by_state' => $n,
                    'sort_by_date' => $this->$var_name_ts,
                    'comment' => $comment
                );
            }
        }

        return $timeline;
    }


    public function delete()
    {
        $this->state->delete();
        $this->deleteMessage();
    }

    
    private function deleteMessage()
    {        
        if(!$this->redis->DEL($this->id))
        {
            throw new \Exception('Error delete message body: '. $this->id);
        }
        
        return $this;
    }

  
    public function republish()
    {       
        $this->incLoopCount();        
        $this->setState(Message\State::PUBLISHED);
            
        return $this;               
    }
    
    
    private function incLoopCount()
    {
        if(!$this->loop_count = $this->redis->HINCRBY($this->id, 'loop_count'))
        {
            throw new \Exception('Error inc loop_count: '. $this->id);
        }
        
        return $this;
    }
    
    
    public function inprocess()
    {
        return $this->state->set(Message\State::INPROCESS);
    }

    
    public function publish()
    {
        return $this->state->set(Message\State::PUBLISHED);
    }

    
    public function processed($comment='')
    {
        return $this->state->set(Message\State::PROCESSED, $comment);
    }


    public function failed($comment='')
    {
        return $this->state->set(Message\State::FAILED, $comment);
    }
     
        
    public function dropped($comment='')
    {
        return $this->state->set(Message\State::DROPPED, $comment);
    }    
    
    
    /**/
    public function prolongate()
    {
    }
    
    


    
    function getState() 
    {
        return $this->state;
    }
    
    
	private function generateId() 
	{
        $message_num = $this->redis->INCR(Message\State::KEY_GLOBAL_MESSAGES_COUNTER);

        //$raw_entropy = uniqid(/*'m:'.$this->queue->getQueueName().':'*/'',true);
		return 'message:'.$this->queue->getQueueName()/*':'.Utils::timeStringForMessageId().*/.':'.Utils::guid().':'.$message_num;
	}

    
    private function setId($id)
    {
        $this->checkId($id);
        $this->id = $id;        
        $this->redis->HSET($this->id, 'id', $this->id);
        
        return $this;
    }


    public function getId()
    {
        return $this->id;
    }


    public function getRedis()
    {
        return $this->redis;
    }

    public function getQueue()
    {
        return $this->queue;
    }




    /**
     * @param string $payload
     * @return RedisQueue\Message
     */
    public function setPayload($payload)
    {        
		$this->checkPayload($payload);

        $this->payload = serialize($payload);
        
        $this->redis->HSET(
               $this->id, 
               'payload',
               $this->payload);
        
        return $this;        
    }

    
    private function checkPayload($payload='') 
    {
        if (!$payload) 
        {
            throw new \Exception('Payload cannot be empty');
        }        
    }


    function getPayload()
    {
        $payload = $this->redis->HGET(
            $this->id,
            'payload');

        $this->checkPayload($payload);

        return unserialize($payload);
    }

    private function checkId($id='') 
    {
        if (!$id) 
        {
            throw new \Exception('ID cannot be empty');
        }        
    }
    
    
    public function load($id='')
    {
        $this->checkId($id);

  //      if(!$this->redis->EXISTS($id))
  //      {
  //          throw new \Exception('Message with id "'.$id.'" not found');
  //      }

        //$this->id = $id;
        $body_hash = $this->redis->HGETALL($id);
        
        if(!$body_hash)
        {
            throw new \Exception('Message with id "'.$id.'" not found');
        }
        
        foreach($body_hash as $field_name=>$field_val)
        {
            $this->$field_name = $field_val;

            //if(substr($field_name,1) != '_')
            //{
            //    $this->$field_name = $field_val;
            //

        }

        $this->state = new Message\State($this);
        
        return $this;        
    }


    private function create()
    {
        $this->setId($this->generateId());

        $this->state = new Message\State($this);
        $this->state->create();
    }



	public function __construct($queue, $id='')	
	{        	        
		$this->queue = $queue;
        $this->redis = $this->queue->getRedis();
        
        if(!$id)
        {
            $this->create();
        }
        else
        {
            $this->load($id);
        }

	}


    /*function __destruct()
    {
        if(!$this->state-isPublished())
        {
            $this->delete();
        }
    }*/
    
}