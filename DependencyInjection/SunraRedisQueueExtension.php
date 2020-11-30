<?php

namespace Sunra\RedisQueueBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SunraRedisQueueExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
		
		$available_queues = array();
		$used_queues = array();
		$used_services = array();
		
		// Normalize queues
		foreach($config['queues'] as $queue_name=>$queue) 
		{
			if ($queue['enabled'])
			{				
				
				
				if (in_array($queue_name, $available_queues)) 
				{
					throw new \Exception("Queue '{$queue_name}' already defined");
				} else 
				{		
				    $config['queues_norm'][$queue_name] = $queue;	
                    $config['queues_norm'][$queue_name]['name'] = $queue_name;	 	
				    $available_queues[] = $queue_name;
				}
				
			}
            
		}
		
        if (!isset($config['queues_norm'])) {
			throw new \Exception('Atleast one queue must be defined and enabled');
		}	
		

		

		// Normalize workers
		foreach($config['workers'] as $worker_name=>$worker) 
		{
			if ($worker['enabled'])
			{
				for($i = 1; $i <= $worker['count']; $i++) 
				{
					$worker['name'] = $worker_name;
					$worker['#'] = (string)$i;
                    $worker['norm_name'] = $worker_name.'.'.$i;

					$config['workers_norm'][$worker['norm_name']] = $worker;

				}
				
				if (!in_array($worker['queue'], $available_queues)) 
				{
					throw new \Exception("Queue '{$worker['queue']}' setted for worker '$worker_name' not found in defined queues (".implode(', ',$available_queues).")");
				}						

				
				if(in_array($worker['queue'], $used_queues)) 
				{
				    throw new \Exception("Queue '{$worker['queue']}' already used in another worker. Only one worker can be subscribed for particular queue");
				} else {
                    $used_queues[/*$worker_name*/] = $worker['queue'];
				}				
				
				if(in_array($worker['service'], $used_services)) 
				{
				    throw new \Exception("Service '{$worker['service']}' already used in another worker. One service can be used only in one worker");
				} else {
                    $used_services[/*$worker_name*/] = $worker['service'];
				}					
							
			}
		}
		
		if (!isset($config['workers_norm'])) {
			throw new \Exception('Atleast one worker must be defined and enabled');
		}
		
		
		
		
		
		
		
		
		
		/*$container
		    ->register('sunra_redis_queue', 'Sunra\RedisQueueBundle\RedisQueue')
		        ->addArgument($config)
				->addArgument(new Reference($config['redis']))
				
				
		;*/
		
		$container
		    ->register('sunra_redis_queue.supervisor', 'Sunra\RedisQueueBundle\RedisQueue\Supervisor')
                ->setPublic(true)
		        ->addArgument($config)
				->addArgument(new Reference($config['redis']))
				->addArgument(new Reference('kernel'))
				//->addArgument(isset($config['logger'])?new Reference($config['logger']):null)
				
		;
        //$container->setAlias('Sunra\RedisQueueBundle\RedisQueue\Supervisor','sunra_redis_queue.supervisor');

        $container->register('Sunra\RedisQueueBundle\Command\SupervisorCommand')
            ->addTag('console.command', ['command' => 'redis-queue:supervisor'])
            ->addArgument(new Reference('sunra_redis_queue.supervisor'))
        ;

		
		foreach($config['workers_norm'] as $worker_name=>$worker) {
			$container
		    ->register('sunra_redis_queue.worker.'.$worker['name'].'.'.$worker['#'], 'Sunra\RedisQueueBundle\RedisQueue\Worker')
		        ->addArgument($config['workers_norm'][$worker_name])
				->addArgument(new Reference($config['redis']))
				->addArgument(new Reference('kernel'))

                ->setPublic(true)
				
		    ;
		}
		
		foreach($config['queues_norm'] as $queue_name=>$queue) {
			$container
		    ->register('sunra_redis_queue.queue.'.$queue_name, 'Sunra\RedisQueueBundle\RedisQueue\Queue')
		        ->addArgument($queue)
				->addArgument(new Reference($config['redis']))
				->addArgument(new Reference('kernel'))
                ->setPublic(true)
				//->addArgument(isset($config['logger'])?new Reference($config['logger']):null)
				
		    ;
		}		
		
		//$container->setAlias('realt5000_proxy', 'r5_proxy');
		//die();
        //$loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
		//$loader->load('services.yml');
    }
	
	
	private function filterDisabledWorkers($config) 
	{
		$configFiltered = array();
		
		
		foreach ($config as $name=>$worker) {
			if ($worker['enabled']) {
				$configFiltered[$name] = $worker;
			}
		}
		
		return $configFiltered;
	}
	
}
