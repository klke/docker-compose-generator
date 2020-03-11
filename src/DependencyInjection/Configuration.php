<?php

namespace Klke\DockerComposeGeneratorBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $tb = new TreeBuilder('docker_compose_generator');

        $node = $tb->getRootNode();

        $node
            ->children()
                ->arrayNode('services')
                    ->useAttributeAsKey('name')
                        ->prototype('array')
                        ->children()
                            ->scalarNode('version')->end()
                            ->scalarNode('port')->end()
                        ->end()
                        # Extra_ports
                        ->fixXmlConfig('extra_port')
                        ->children()
                            ->arrayNode('extra_ports')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('name')->end()
                                        ->scalarNode('port')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        # Options
                        ->fixXmlConfig('option')
                            ->children()
                                ->arrayNode('options')
                                    ->prototype('array')
                                    ->children()
                                        ->scalarNode('name')->end()
                                        ->scalarNode('value')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $tb;
    }
}