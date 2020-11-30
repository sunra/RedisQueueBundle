<?php

namespace Sunra\RedisQueueBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Sunra\RedisQueueBundle\RedisQueue;

class WorkerCommand extends ContainerAwareCommand
{
	private $output;

	/** @var  RedisQueue\Supervisor $supervisor */
	private $supervisor;
    private $debug = false;
    private $worker_name;
    private $worker_num_id;
	
	
    protected function configure()
    {
        $this
            ->setName('redis-queue:worker')
            ->setDescription('Queue Worker Command')
			->addArgument('worker', InputArgument::REQUIRED, 'Worker name')
			->addArgument('num_id', InputArgument::OPTIONAL, 'Worker id ', '1')
		;
    }


    protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->output = $output;
		$this->supervisor = $this->getContainer()->get('sunra_redis_queue.supervisor');
		
		$this->worker_name = $input->getArgument('worker');
		$this->worker_num_id = $input->getArgument('num_id');
		
			
		
        
		$output->writeln('');
		$output->writeln('-== <info>Redis Queue Worker</info> ==-');
		$output->writeln('');
		$output->writeln('  Worker name: <comment>'.$this->worker_name.'</comment>');
		$output->writeln('  Worker num id #: <comment>'.$this->worker_num_id.'</comment>');
		
		$output->writeln('');


		$this->start_signal();

		$output->writeln('-= End =-');
		$output->writeln('');
    }
	
	
	private function start_signal()
    {
		$output = $this->output;
		
		$output->writeln('  Signal: <comment>Start</comment>');
		$output->writeln('');
		
		if(!$this->supervisor->state->isActive()) {
 		    $output->writeln('Can not start: <error>Supervisor is not working</error>');
		    $output->writeln('');
			
			return;
		}

		/** @var RedisQueue\Worker $free_worker */
		$free_worker = $this->supervisor->getFreeWorker($this->worker_name.'.'.$this->worker_num_id);
		
		if (!$free_worker) 
		{
			$output->writeln('<error>All configured workers already runing</error> See DEBUG mode in docs');
		    $output->writeln('');
			
			return;
		}
		
        $free_worker->setOutput( $this->output );
		$free_worker->start();
	}

	
}