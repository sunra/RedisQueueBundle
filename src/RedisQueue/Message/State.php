<?php
namespace Sunra\RedisQueueBundle\RedisQueue\Message;

use Sunra\RedisQueueBundle\RedisQueue;


class State 
{
    const CREATED   = 'CREATED';
    const PUBLISHED = 'PUBLISHED';
    const INPROCESS = 'INPROCESS';
    const PROCESSED = 'PROCESSED';    
    const FAILED    = 'FAILED';
    const DROPPED   = 'DROPPED';  

    const KEY_GLOBAL_MESSAGES_COUNTER = 'COUNTER:GLOBAL:MESSAGES_TOTAL';

    /** @var \Predis\Client $redis */
    private $redis;


 
    static public function listAll()
    {
        return [self::CREATED, self::PUBLISHED, self::INPROCESS, self::PROCESSED, self::FAILED, self::DROPPED ];
    }
   
    
    static public function check($state='') 
    {
        if(!$state)
        {
            throw new \Exception('State cannot be empty');
        }
        
        if( !in_array($state, self::listAll()) )
        {
            throw new \Exception('Unknown state: "'.$state. '". Available states are ('.implode(', ', self::listAll()).')');
        }
    }


    static private function getStateHashFieldName()
    {
        return '_state';
    }


    static public function getStateTimestampFieldName($state)
    {
        return '_ts_'.$state;
    }

    static public function getStateCommentFieldName($state)
    {
        return '_comment_'.$state;
    }


    /*
    static public function checkChange($state_from='', $state_to='') 
    {
        if(!$state_to)
        {
            throw new \Exception('State cannot be changed to empty');
        }

        
        if( !in_array($state_to, self::listAll()) )
        {
            throw new \Exception('Unknown state: "'.$state. '". Available states are ('.implode(', ', self::listAll()).')');
        }
    }
*/
    public function delete()
    {
        $state = $this->get();

        if (!$this->redis->SREM(
            $this->queue->getRedisKeyName($state),
            $this->message->getId()) )
        {
            throw new \Exception('Error delete message pointer: '. $this->message->getId(). ' from : '.$this->queue->getRedisKeyName($state) . 'state: '.$state);
        }
    }


    public function get()
    {
        return $this->redis->HGET($this->message->getId(), self::getStateHashFieldName());
    }


    public function isPublished()
    {
        return (self::CREATED != $this->get());
    }


    public function create()
    {
        $state = self::CREATED;

        $this->redis->HSET($this->message->getId(), self::getStateHashFieldName(), $state);

        $this->setTimestamp($state);

        if( !$this->redis->SADD(
            $this->queue->getRedisKeyName($state),
            $this->message->getId()))
        {
            throw new \Exception('Error add message state pointer, '. $this->message->getId());
        }
    }


    public function set($state, $comment = '')
    {
        self::check($state);

        $lua = <<<LUA

-- determine previous state and queue key
local prev_state = redis.call('HGET', KEYS[1], ARGV[1]);
local prev_state_queue_key = ARGV[3]..prev_state;

-- change queue
redis.call('SMOVE', prev_state_queue_key, KEYS[2], KEYS[1]);

-- set mark in message hash
redis.call('HSET', KEYS[1], ARGV[1], ARGV[2]);

-- set state timestamp
redis.call('HSET', KEYS[1], ARGV[4], ARGV[5]);

-- set state comment
redis.call('HSET', KEYS[1], ARGV[6], ARGV[7]);

LUA;

        $this->redis->EVAL($lua,
            2,

            $this->message->getId(),                    // KEYS[1]
            $this->queue->getRedisKeyName($state),      // KEYS[2]

            self::getStateHashFieldName(),              // ARGV[1]
            $state,                                     // ARGV[2]
            $this->queue->getRedisKeyName('', true),    // ARGV[3]
            self::getStateTimestampFieldName($state),   // ARGV[4]
            time(),                                     // ARGV[5]
            self::getStateCommentFieldName($state),     // ARGV[6]
            $comment                                    // ARGV[7]
        );

        //$this->setTimestamp($state);
    }


    private function setTimestamp($state)
    {
        $this->redis->HSET(
            $this->message->getId(),
            self::getStateTimestampFieldName($state),
            time());
    }


    public function getTimestamp($state)
    {
        return $this->redis->HGET(
            $this->message->getId(),
            self::getStateTimestampFieldName($state));
    }


    public function incGlobalMessagesCounter()
    {
        return $this->redis->INCR(self::KEY_GLOBAL_MESSAGES_COUNTER);
    }


    public function __construct(RedisQueue\Message $message)
    {
        $this->message = $message;
        $this->redis = $this->message->getRedis();
        $this->queue = $this->message->getQueue();
    }
    
   
}