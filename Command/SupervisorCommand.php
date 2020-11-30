<?php

namespace Sunra\RedisQueueBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use \Sunra\RedisQueueBundle\RedisQueue;


class SupervisorCommand extends Command
{
	private $output;

    protected static $defaultName = 'redis-queue:supervisor';

	private $supervisor;


    public function __construct(RedisQueue\Supervisor $supervisor)
    {
        $this->supervisor = $supervisor;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Queue Supervisor Control command')
			->addArgument('signal', InputArgument::REQUIRED, 'start|stop|cron_start Supervisor')
            ->addUsage('start - Start manually. Removes STOP lock')
            ->addUsage('stop - Stop manually. Set STOP lock, that prevents from running.')
            ->addUsage('cron_start - crontab event receiver. Start Supervisor from cron. if not stopped manually and not already working')
            ->addUsage('run -vvv - Run Supervisor synchronously for DEBUG. Normally called internally')
		;
    }


    protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->output = $output;
        $this->supervisor->setOutput($this->output);
				
		$signal = $input->getArgument('signal');
        
		$output->writeln('');
		$output->writeln('-== <info>Redis Queue Supervisor</info> ==-');
		$output->writeln('');
		
		
		if($signal == 'start')
        {
			$this->start_signal();
		}
		elseif($signal == 'cron_start')
        {
			$this->cron_start_signal();
		}
		elseif($signal == 'stop')
        {
			$this->stop_signal();
		}		
		elseif($signal == 'run')
        {
			$this->run_signal();
		}
		else
        {
			$output->writeln('Unknown signal');
			$output->writeln('');
		}

		$output->writeln('= End =');		
		$output->writeln('');
				
    }
	
	
	private function start_signal() {
		$output = $this->output;
		
		$output->writeln('= Signal <comment>Start</comment> =');
		$output->writeln('');
		
		if($this->supervisor->state->isActive()) {
 		    $output->writeln('Can not start: <error>Already runing</error>');
		    $output->writeln('');
			
			return;
		}

        if($this->supervisor->start()) {
            $output->writeln('Start: <info>OK</info>');
        }

	}
	
	
	private function stop_signal() {
		
		$output = $this->output;
		
		$output->writeln('= Signal <comment>Stop</comment> =');
		$output->writeln('');

		if(!$this->supervisor->state->isActive()) {
 		    $output->writeln('Can not stop: <error>Not runing</error>');
		    $output->writeln('');
			
			return;
		}
		
		if($this->supervisor->stop()) {
            $output->writeln('Stop: <info>OK</info>');
            $output->writeln('');
        };
		
	}


	private function cron_start_signal() {
		
		$output = $this->output;
		
		$output->writeln('= Signal <comment>Cron Start</comment> =');
		$output->writeln('');
		
		if($this->supervisor->state->isActive()) {
 		    $output->writeln("Can't cron_start: <error>Already runing</error>");
		    $output->writeln('');
			
			return;
		}

		if($this->supervisor->state->isStopped()) {
 		    $output->writeln('Can not cron_start: <error>Stopped manualy</error>');
		    $output->writeln('');
			
			return;
		}
		
		$this->supervisor->start();
	}

	

	private function run_signal() {
		
		$output = $this->output;
		
		$output->writeln('= Signal <comment>Run</comment> =');
		$output->writeln('');
		
		if($this->supervisor->state->isActive()) {
            $output->writeln('Can not run: <error>Already runing</error>');
		    $output->writeln('');
            
			return;
		}		
		
		$this->supervisor->run();
	}


	
}