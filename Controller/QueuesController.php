<?php

namespace Sunra\RedisQueueBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
//use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

// these import the "@Route" and "@Template" annotations
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/queues") 
 */
class QueuesController extends Controller
{
    /**
     * @Route("/", name="sunra_redis_queue_queues")
     * @Template("SunraRedisQueueBundle::queues.html.twig")
     */
    public function indexAction()
    {
		$sv = $this->get('sunra_redis_queue.supervisor');
		//echo '<pre>';
		//var_dump($sv->config);die();
		//echo '</pre>';
		
        return array('config' => $sv->config, 'state' => $sv->state->get());
    }
	
	
	/**
     * @Route("/state", name="sunra_redis_queue_queues_state")     
     */
    public function queuesStateAction()
    {

        $sv = $this->get('sunra_redis_queue.supervisor');		
		
		$state = array();

		
		foreach($sv->config['queues_norm'] as $queue_name_norm=>$queue)
		{
            $state[$queue_name_norm] = $this->get('sunra_redis_queue.queue.'.$queue_name_norm)->getSizes();
		}
				
		
        return new JsonResponse(
		    array(
		      'state' => $state,
			  'queues_norm' => $sv->config['queues_norm'],
              'messages_total_counter' => $sv->getTotalMessagesCount()
		    )
		);
		
    }
    
    /**
     * @Route("/clear", name="sunra_redis_queue_queues_clear") 
     * @Method("POST")   
     */
    public function clearQueueAction(Request $request)
    {
       $payload = json_decode($request->getContent());
       
       $queue_name = $payload->queue_name;
       $queue_state = $payload->queue_state;
       
        
       $queue = $this->get('sunra_redis_queue.queue.'.$queue_name);
        
       $text = "$queue_name.$queue_state cleared";        
        
       $ok = $queue->clearAsync($queue_state);
        
        if (!$ok) 
        {
            $text = "$queue_name.$queue_state clearing error"; 
        }
        
        return new JsonResponse(
		    array(
		      'ok' => $ok,
			  'text' => $text
		    )
		);  
        
    }    
    
    
    /**
     * @Route("/show", name="sunra_redis_queue_queues_show")
     * @Template("SunraRedisQueueBundle::queues.show.html.twig")
     */
    public function showQueueAction(Request $request)
    {
		$payload = json_decode($request->getContent());
       
        $queue_name = $payload->queue_name;
        $queue_state = $payload->queue_state;
        
        $queue = $this->get('sunra_redis_queue.queue.'.$queue_name);
        
        $messages = $queue->show($queue_state, 100, 0);
        
        
        if($ok) {
            $text = "$queue_name.$queue_state restarted";
        } else {
            $text = "$queue_name.$queue_state restarting error";
        }
	
        return new JsonResponse(
		    array(
		      'ok' => $ok,
			  'text' => $text,
              'messages' => $messages,
		    )
		);
    }


   /**
     * @Route("/restart", name="sunra_redis_queue_queues_restart")     
     */
    public function restartQueueAction(Request $request)
    {        
        $payload = json_decode($request->getContent());
       
        $queue_name = $payload->queue_name;
        $queue_state = $payload->queue_state;
        
        $queue = $this->get('sunra_redis_queue.queue.'.$queue_name);
        
        $ok = $queue->republish($queue_state);
        
        if($ok) {
            $text = "$queue_name.$queue_state restarted";
        } else {
            $text = "$queue_name.$queue_state restarting error";
        }
	
        return new JsonResponse(
		    array(
		      'ok' => $ok,
			  'text' => $text
		    )
		);
    }


	/**
     * @Route("/start", name="sunra_redis_queue_supervisor_start")     
     */
    public function supervisorStartAction()
    {
		$result = $this->get('sunra_redis_queue.supervisor')->start();
		
        return new JsonResponse(
		    array(
		      'result' => $result
		    )
		);
    }


	/**
     * @Route("/stop", name="sunra_redis_queue_supervisor_stop")     
     */
    public function supervisorStopAction()
    {
		$result = $this->get('sunra_redis_queue.supervisor')->stop();
		
        return new JsonResponse(
		    array(
		      'result' => $result
		    )
		);
    }
	
	
	
	
	


}
