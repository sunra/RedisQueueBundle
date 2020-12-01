<?php

namespace Sunra\RedisQueueBundle\RedisQueue;

use Symfony\Component\Console\Output\OutputInterface;

use Sunra\RedisQueueBundle\RedisQueue;


class Worker
{
    /** @var RedisQueue\AbstractWorker $service */
	private $service;

	/** @var RedisQueue\Supervisor $supervisor */
	private $supervisor;



	private $config;
    
    private $debug;

    /** @var OutputInterface $output */
    private $output;

    /** @var RedisQueue\Queue $queue */
    private $queue;

    /** @var Worker\State $state */
	public $state;
	
	const HARD_ITERATION_MAXIMUM = 100000;


    public function __construct($config, $redis, $kernel)
    {
        $this->state = new Worker\State($config, $redis);
        $this->supervisor = $kernel->getContainer()->get('sunra_redis_queue.supervisor');

        $this->queue = $kernel->getContainer()->get('sunra_redis_queue.queue.'.$config['queue']);
        $this->debug = $config['debug'];
        $this->config = $config;

        if($kernel->getContainer()->has($config['service']))
        {
            $this->service = $kernel->getContainer()->get($config['service']);
        } else
        {
            $this->state->setError('User\'s worker service not found: '.$config['service']);
            $this->dmsg('User\'s worker service: '.$config['service'], 'Not Found', 'FATAL ERROR: ');
        }



    }

	
	public function start() 
	{	
	    $this->state->setPid();
		$this->state->setTimeStart();
        $this->state->zeroIterations();
		
        if($this->debug)
        {
            $this->dmsg('DEBUG mode is', 'ON');
        }
        
        $this->dmsg('  Connected queue: "', trim($this->config['queue']).'"');

        $this->dmsg(var_export( $this->config));
                
		$this->run();	    
		
		$this->state->del();
		
		//die();
    }

    public function exception_error_handler($severity, $message, $file, $line) {
        //if (!(error_reporting() & $severity)) {
        ///    // This error code is not included in error_reporting
        //    return;
        //}
        throw new \ErrorException($message, 0, $severity, $file, $line);
    }


    /**
     * @return Worker\Result
     */
    private function callWorkerService(Message $message)
    {
        try
        {
            /** @var Worker\Result $result */
            $result = $this->service->run($message->getPayload(), $message);

            if(is_bool($result)) {
                $result = new Worker\Result($result);
            } elseif (is_string($result)) {
                $result = new Worker\Result(true, $result);
            }
        }
        catch (\Exception $e)
        {
            //echo 'Выброшено исключение: ',  $e->getMessage(), "\n";

            $result = new Worker\Result(false, $e);
            //$result = false;
            //$failed_msg = $e;//->getMessage();
        }
        catch (\ErrorException $e) {
            //echo 'Выброшено исключение: ',  $e->getMessage(), "\n";

            //$result = false;
            //$failed_msg = $e;//->getMessage();
            $result = new Worker\Result(false, $e);

        }

        $this->dmsg('calling result ', $result?'TRUE':'FALSE', 'User worker service');

        return $result;
    }


    private function run()
	{
        //set_error_handler([&$this, "exception_error_handler"]);
        //             restore_error_handler();

		while( true ) {
            
			//$this->checkWorkers();
			
//			$this->keepalive();
            $curIteration = $this->state->incIterations();
            
            $this->dmsg('  ', $curIteration,'Iteration');
            
		    $this->state->setTimeCur();
			
			/*
			 get message
			 if message
			    run worker
			 else
			    wait
			*/
			
			$message = $this->queue->read();


            if (!$message)
            {
                $this->dmsg('queue is', 'Empty', 'NO MESSAGES');

            } else {

                //$this->dlog($this->config['norm_name'],['message readed', $message->getId()]);

                $this->supervisor->state->incCounter('GLOBAL_MESSAGES_READED');

                $this->state->incCounter('MESSAGES_READED');

                $this->dmsg($this->config['norm_name'],'message readed', $message->getId());


                $result = $this->callWorkerService($message);

                if($result->isOk())
                {
                    $this->supervisor->state->incCounter('GLOBAL_MESSAGES_PROCESSED');
                    $this->state->incCounter('MESSAGES_PROCESSED');

                    $message->processed($result->getComment());

                    $this->dmsg('PROCESSED', $this->queue->getSize(Message\State::PROCESSED), 'COUNT');

                    $this->dmsg('message', 'processed', $message->getId());
                } else
                {
                    $this->supervisor->state->incCounter('GLOBAL_MESSAGES_FAILED');
                    $this->state->incCounter('MESSAGES_FAILED');

                    $message->failed($result->getComment());

                    $this->dmsg('message', 'failed', $message->getId());
                }
            }




			
			if($this->stopSignalArrived())			
			{
                $this->dmsg('stopSignal', 'Arrived');
                
				break;
			}
            
            if ($curIteration >= $this->config['max_iterations'])
			{
                $this->dmsg('USER_ITERATION_MAXIMUM', 'Reached');
                
				break;
			}
            
			if ($curIteration >= self::HARD_ITERATION_MAXIMUM)
			{
                $this->dmsg('HARD_ITERATION_MAXIMUM', 'Reached');
                
				break;
			}

			if(false or $this->config['#'] == '1') { // Only one worker can do trim to avoid parallel trimming
                $this->queue->trim(Message\State::PROCESSED);
                $this->queue->trim(Message\State::FAILED);
            }

            $this->queue->droppedMessagesDetection(Message\State::INPROCESS);
            $this->queue->droppedMessagesDetection(Message\State::CREATED);
            
            if($this->config['debug'])
            {
                usleep($this->config['sleep_interval_DEBUG']);
            }
            elseif(!$message)
            {
                usleep($this->config['sleep_interval_NO_MESSAGES']);
            }
            else 
            {
                usleep($this->config['sleep_interval']);
            }

            //restore_error_handler();
		}
	}
	
	
	public function stopSignalArrived() 
	{
		if ( !$this->supervisor->state->isActive() )
		{            
			return true;
		}
	}
	
	
	public function stop()
	{
		$this->state->del();
	}
	
    
	private function dmsg($text, $highlite='', $title='')
    {
        //if(!$this->debug || !isset($this->output)) return;

        if (!$this->output->isVeryVerbose()) return;
        
        $this->output->writeln("<info>$title</info> $text <comment>$highlite</comment>");
        
    }



    
    
    public function setOutput($output) 
    {
        $this->output = $output;
        
        return $this;
    }

	
}