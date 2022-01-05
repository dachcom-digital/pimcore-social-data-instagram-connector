<?php

namespace SocialData\Connector\Instagram\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('social_data_instagram_connector');
        $rootNode = $treeBuilder->getRootNode();

        return $treeBuilder;
    }
}
