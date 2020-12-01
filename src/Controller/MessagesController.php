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
 * @Route("/messages")
 */
class MessagesController extends Controller
{
    /**
     * @Route("/{queue_name}/{queue_state}", name="sunra_redis_queue_messages_show", options={"expose"=true})
     * @Template("SunraRedisQueueBundle::messages.show.html.twig")
     */
    public function showMessagesAction($queue_name, $queue_state, Request $request)
    {
		//$payload = json_decode($request->getContent());

        //$queue_name = $payload->queue_name;
        //$queue_state = $payload->queue_state;

        //$ok = true;
        
        //$queue = $this->get('sunra_redis_queue.queue.'.$queue_name);
        
        //$messages = $queue->show($queue_state, 100, 0);

        return array('queue_name' => $queue_name, 'queue_state' => $queue_state, );
    }


    /**
     * @Route("/get/{queue_name}/{queue_state}/{page}-{num_messages}", name="sunra_redis_queue_messages_get", options={"expose"=true})
     */
    public function getMessagesAction($queue_name, $queue_state, $page, $num_messages, Request $request)
    {
        //$payload = json_decode($request->getContent());

        //$queue_name = $payload->queue_name;
        //$queue_state = $payload->queue_state;
        //set_time_limit(0);
        $ok = true;

        $queue = $this->get('sunra_redis_queue.queue.'.$queue_name);

        $offset = ($page-1) * $num_messages;
        $messages = $queue->showInfo($queue_state, $num_messages, $offset);


        if($ok) {
            $text = "ok";
        } else {
            $text = "error";
        }

        return new JsonResponse(
            array(
                'ok' => $ok,
                'text' => $text,
                'messages' => $messages,
                'total_messages' => $queue->getSize($queue_state)
            )
        );
    }
	
	
	
	


}
