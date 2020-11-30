<?php

namespace Sunra\RedisQueueBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
//use Symfony\Component\HttpFoundation\RedirectResponse;


// these import the "@Route" and "@Template" annotations
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/config") 
 */
class ConfigController extends Controller
{
    /**
     * @Route("/", name="sunra_redis_queue_config")
     * @Template("SunraRedisQueueBundle::config.html.twig")
     */
    public function indexAction()
    {
		$sv = $this->get('sunra_redis_queue.supervisor');
		//echo '<pre>';
		//var_dump($sv->config);die();
		//echo '</pre>';
		
        return array(
		    'config' => $sv->config, 
		    'state' => $sv->state->getHuman()
		);
    }
	

	
	
	


}
