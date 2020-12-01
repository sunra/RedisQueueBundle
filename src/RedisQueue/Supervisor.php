<?php

namespace Sunra\RedisQueueBundle\RedisQueue;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;


class Supervisor
{
	public $state;
	
	/** @var OutputInterface $output */
	private $output;

	public $config;
	private $redis;
    
	private $kernel;
	private $container;

    private $process;
    private $worker_processes;


    function __construct($config, $redis, $kernel)
    {
        $this->config = $config;
        $this->redis = $redis;
        $this->kernel = $kernel;

        $this->state = new Supervisor\State($redis, $config);
    }


    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }


	public function start() 
	{		
		if ($this->state->isActive()) 
		{
		    return false;
		}
		
		$this->state->delStopped();
		
	    return $this->asyncRun();
	}
	
	
	public function cron_start() 
	{		
		if ($this->state->isStopped()) {
		    return false;
		}
		
		return $this->start();		
	}
	
	
	public function stop() 
	{
		if (!$this->state->isActive()) {
		    return false;
		}		
		
		
		$this->state->setStopped();
		
		$last_line = system('kill '.$this->state->getPid());
		
		//$this->state->del();
		
		return ($last_line !== false);				
	}
	
	
	private function asyncRun()
    {
        $this->process = new Process($this->kernel->getRootDir().'/../bin/console redis-queue:supervisor run');

        $this->process->start();

        return $this->process->isStarted();
	}
	
	
	private function dmsg($text, $highlite='', $title='')
    {
        if ($this->output->isVeryVerbose())
        {
            $this->output->writeln("<info>$title</info> $text <comment>$highlite</comment>");
        }
    }

	
	public function run()
	{		
		$this->state->setPid();

        $this->dmsg("Supervisor <info>RUNNING</info>. PID: <info>{$this->state->getPid()}</info>");


		$this->state->setConfig($this->config);
		$this->state->setTimeStart();
        $this->state->clearIterations();

		//var_dump($this->state);
		
		//$this->spinUpWorkers();
	
	    while(1) {

            $this->state->incIterations();

//			$this->checkSignals();
			$this->checkWorkersRunning();
			
//			$this->keepalive();
		    $this->state->setTimeCur();

            sleep(1);//usleep(25000);
		}
		
	}
	
	

	private function checkWorkersRunning() {
		
			/*
			if worker is down
			   up worker
			*/


        //$this->dmsg(var_export($this->config['workers_norm']));
		foreach($this->config['workers_norm'] as $worker_norm_name=>$worker) {

		    $this->dmsg("Checking worker <info>$worker_norm_name</info>");

		    //$this->dmsg(var_export($worker));



            if($worker['debug'])
            {
                if($this->getWorkerServiceExternal($worker_norm_name)->state->isActive()) {
                    $this->dmsg("  worker <info>OK</info>");
                } else {
                    $this->dmsg("  This worker must be manually running. <error>DEBUG</error>");
                }

                continue;
            }

            
			if (!$this->isWorkerRunning($worker) )
			{
                $this->dmsg("  worker <error>Not working</error>");

			    $this->startWorker($worker);
			} else {
                $this->dmsg("  worker <info>OK</info>");
            }



		}
	}

	
	/*private function spinUpWorkers() 
    {
		foreach($this->config['workers_norm'] as $worker) {
			$this->asyncRunWorker($worker);			
		}
	}*/
	

	public function getFreeWorker($worker_norm_name_external)
	{
		$worker_found = false;
		
	    foreach($this->config['workers_norm'] as $worker_norm_name=>$worker) 
		{
			if($worker_norm_name == $worker_norm_name_external)
			{
				$worker_found = true;

				//if(isset($this->worker_processes[$worker['norm_name']]) && $this->worker_processes[$worker['norm_name']]->IsRunning())


				if(!$this->getWorkerServiceExternal($worker_norm_name)->state->isActive())
				{ 
				    return $this->getWorkerServiceExternal($worker_norm_name);
				}
				
			}
		}	
		
		if(!$worker_found) 
		{
		    throw new \Exception('Worker with name "'.$worker_name.'" not found');
		}
		
		return false;
	}


	/** @return Sunra\RedisQueueBundle\RedisQueue\Worker */
	public function getWorkerServiceExternal($worker_norm_name) 
	{		
		return $this->kernel->getContainer()->get('sunra_redis_queue.worker.'.$worker_norm_name);		
	}
	
	
	private function startWorker($worker)
	{
	    $worker_command_array =
        [
	        $this->kernel->getRootDir() . '/../bin/console redis-queue:worker',
            $worker['name'],
            $worker['#']
        ];

	    if($this->output->isDebug())
	    {
            $worker_command_array[] = '-vv';
        }

        $worker_command = implode(' ', $worker_command_array );

        $this->worker_processes[$worker['norm_name']] = new Process($worker_command);
        $process = $this->worker_processes[$worker['norm_name']];

        $process->setTimeout(0);


        $process->start(); //sleep(1);



        //$this->dmsg(var_export($this->worker_processes[$worker['norm_name']]));

        if($process->isStarted()) {
            $this->dmsg("  Started <info>OK</info>. PID: ".$process->getPid());

            //$this->dmsg($process->getOutput());

            return;
        }



        $max_trys = 5;
        $try = 0;
        while(1)
        {
            sleep(1);
            if($this->isWorkerRunning($worker) /*$process->isRunning()*/)
            {
                $this->dmsg("  Working <info>OK</info>");
                //$this->dmsg($process->getOutput());

                break;
            }

            $try++;

            if($try >= $max_trys)
            {
                $this->dmsg($process->getErrorOutput());
                $this->dmsg($process->getOutput());

				$process->getIncrementalErrorOutput();
				/*
                file_put_contents(
					'/tmp/worker_error_'.serialize($worker['name']),
					$process->getIncrementalErrorOutput(),
					FILE_APPEND
					);
                */

                throw new \Exception("Can not start worker's process");
            }

        }


	}


    private function isWorkerRunning($worker)
    {
        //if($this->getWorkerServiceExternal($worker['norm_name'])->state->isActive())
        //{
        //    return true;
        //}

        if(!isset($this->worker_processes[$worker['norm_name']])) {
            // worker not even started yet

            return false;
        }

        /** @var Process $process */
        $process = $this->worker_processes[$worker['norm_name']];

        if($process->IsRunning())
        {
            return true;
        }

        //$this->dmsg(var_export($process));

        $this->dmsg($process->getOutput());
        $this->dmsg($process->getErrorOutput());


        return false;
    }




    public function getTotalMessagesCount()
    {
        return $this->redis->GET(Message\State::KEY_GLOBAL_MESSAGES_COUNTER);
    }
	

}