<?php

namespace Sunra\RedisQueueBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
//use Symfony\Component\HttpFoundation\RedirectResponse;


// these import the "@Route" and "@Template" annotations
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/workers") 
 */
class WorkersController extends Controller
{
    /**
     * @Route("/", name="sunra_redis_queue_workers")
     * @Template("SunraRedisQueueBundle::workers.html.twig")
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
     * @Route("/state", name="sunra_redis_queue_workers_state")     
     */
    public function workersStateAction()
    {

        $sv = $this->get('sunra_redis_queue.supervisor');		
		
		$state = array();
		
		foreach($sv->config['workers_norm'] as $worker_name_norm=>&$worker)
		{
			$state[$worker['name']][$worker['#']] = $sv->getWorkerServiceExternal($worker_name_norm)->state->getHuman();
		}
		
		
		
		
		
        return new JsonResponse(
		    array(
		      'state' => $state,
			  'workers_norm' => $sv->config['workers_norm']
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
