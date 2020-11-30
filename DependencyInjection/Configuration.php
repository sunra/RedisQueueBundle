<?php

namespace Sunra\RedisQueueBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('sunra_redisqueue');

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.
		
		$rootNode
		    ->children()
                ->scalarNode('redis')
			        ->isRequired()
					//->cannotBeEmpty()
			    ->end()
				->arrayNode('queues')				    
					->isRequired()
					->requiresAtLeastOneElement()
					
					/*->useAttributeAsKey('name')*/
					->prototype('array')
					    
					    ->beforeNormalization()
						    ->ifString()
							->then(function($v) { return array('name'=> $v); })
						->end()
					    ->canBeDisabled()				    
						//->cannotBeEmpty()
						->isRequired()
						->children()
                            ->scalarNode('name')->end()
                            ->scalarNode('description')->end()
                            ->integerNode('timeout_INPROCESS')
							    ->defaultValue(3600)
								->min(10)
							->end()
                            ->integerNode('timeout_CREATED')
                                ->defaultValue(3600)
                                ->min(10)
                            ->end()
                            ->integerNode('default_message_TTL')
							    ->defaultValue(0)
								->min(0)
							->end()
                            ->integerNode('max_messages_PROCESSED')
							    ->defaultValue(10000)
								->min(1)
							->end()
                            ->integerNode('max_messages_FAILED')
                                ->defaultValue(10000)
                                ->min(1)
                            ->end()
                        ->end()		
						
					->end()					
				->end()						
				->arrayNode('workers')		    
				    ->prototype('array')
					    ->canBeDisabled()
    				    ->children()						    
                            ->scalarNode('service')
							    ->isRequired()
								->cannotBeEmpty()
							->end()
							->integerNode('count')
							    ->min(1)
							    ->defaultValue(1)
							->end()
							->scalarNode('cron')->end()
							->scalarNode('max_time')
							    ->defaultValue("")
							->end()
							->integerNode('max_iterations')
							    ->defaultValue(10000)
								->min(0)
							->end()
                            ->integerNode('sleep_interval')
							    ->defaultValue(100000)
								->min(25000)
							->end()
                            ->integerNode('sleep_interval_NO_MESSAGES')
							    ->defaultValue(3000000)
								->min(25000)
							->end()
                            ->integerNode('sleep_interval_DEBUG')
							    ->defaultValue(3000000)
								->min(25000)
							->end()
                            
                            ->integerNode('timeout')
							    ->defaultValue(600)
								->min(1)
							->end()
							->scalarNode('keepalive_interval')
							    ->defaultValue("")
							->end()
							->scalarNode('cron')
							    ->defaultValue("")
							->end()
							
							->scalarNode('queue')
							    ->isRequired()								
							->end()				
							->booleanNode('debug')
							    ->defaultValue(false)
							->end()

							
					    ->end()
					->end()
				->end()
 	  
        ;

        return $treeBuilder;
    }
}
