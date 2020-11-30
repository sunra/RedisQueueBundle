<?php

namespace Sunra\RedisQueueBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class QueueClearCommand extends ContainerAwareCommand
{
	private $output;


    protected function configure()
    {
        $this
            ->setName('redis-queue:queue:clear')
            ->setDescription('Queue Clear Command')
			->addArgument('queue_name', InputArgument::REQUIRED, 'Queue name (string)')
            ->addArgument('queue_state', InputArgument::REQUIRED, 'Queue state (string)')
		;
    }


    protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->output = $output;
		$supervisor = $this->getContainer()->get('sunra_redis_queue.supervisor');
		
		$queue_name = $input->getArgument('queue_name');
        $queue_state = $input->getArgument('queue_state');

        $queue = $this->getContainer()->get('sunra_redis_queue.queue.'.$queue_name);

        $text = "$queue_name.$queue_state cleared";

        $ok = $queue->clear($queue_state);

        
		$output->writeln('');
		$output->writeln('');
		$output->writeln(' Qlear queue: <comment>'.$queue_name.':'.$queue_state.'</comment>');


		$output->writeln('= End =');
		$output->writeln('');
    }

}