<?php

namespace Sunra\RedisQueueBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;


use Sunra\RedisQueueBundle\RedisQueue;



/**
 * @Route("/test") 
 */
class TestController extends Controller
{
    /**
     * @Route("/", name="sunra_redis_queue_test")
     * @Template("SunraRedisQueueBundle::test.twig.html")
     */
    public function indexAction()
    {

        //$this->get('sunra_redis_queue.queue.inc_show_counter')->publish(array('test'));
        $this->get('sunra_redis_queue.queue.exception')->publish(array('test'));

        die();
        $lua = <<<LUA
local msg = "Hello, world!"
return msg
LUA;


       $redis =  $this->get('snc_redis.queues');


       $res = $redis->EVAL($lua,0);

       var_dump($res);

       die();


        $sv = $this->get('sunra_redis_queue.supervisor');
		
		
		
		$queue_dummy = $this->get('sunra_redis_queue.queue.inc_show_counter');
        
        //$size = $queue_dummy->getSizes();
echo '<pre>';
        //var_dump($size);
//echo '</pre>';        
        
        //$queue_dummy->clear('DROPPED');
        //$queue_dummy->clearAll();
        //$queue_dummy->getMessage($id);
		
		/*$message = new RedisQueue\Message('test');		
		$message->publish($queue_dummy);*/
        
        $message = $queue_dummy->publish('test'); // POSTED  
        
        
        
        //$message1 = $queue_dummy->read();
        
        var_dump($message); 
        
        //$message1->processed();
        //$message1->failed();
        //var_dump($message1);     
        //$message1->republish();
        
        //$message = $queue_dummy->read(); // INPROCESS        
        

        //$message1->delete(); 
        
    
die();		
        return array(
		    'config' => $sv->config, 
		    'state' => $sv->state->getHuman()
		);
    }
	

	
	
	


}
