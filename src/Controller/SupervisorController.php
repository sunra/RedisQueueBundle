<?php

namespace Sunra\RedisQueueBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;


/**
 * @Route("/") 
 */
class SupervisorController extends Controller
{
    /**
     * @Route("/", name="sunra_redis_queue")
     * @Template("SunraRedisQueueBundle::layout.html.twig")
     */
    public function indexAction()
    {
		$sv = $this->get('sunra_redis_queue.supervisor');
		//echo '<pre>';
		//var_dump($sv->config);die();
		//echo '</pre>';
		//echo '<pre>';
        //var_dump($_SERVER);die();
        return array(
		    'config' => $sv->config, 
		    'state' => $sv->state->getHuman(),            
		);
    }
	
	
	/**
     * @Route("/data", name="sunra_redis_queue_supervisor_state")     
     */
    public function supervisorStateAction()
    {

		$state = $this->get('sunra_redis_queue.supervisor')->state->getHuman();
		
        return new JsonResponse(
		    array(
		      'state' => $state
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
