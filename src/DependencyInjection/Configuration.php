<?php

namespace Lordjancso\TranslationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('lordjancso_translation');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('managed_locales')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->validate()
                        ->always(function ($value) {
                            foreach ($value as $i => $val) {
                                if (!is_string($val)) {
                                    throw new InvalidTypeException(sprintf('Invalid type for path "%s". Expected string, but got %s at array position #%s', 'lordjancso_translation.managed_locales', gettype($val), $i));
                                }
                            }

                            return $value;
                        })
                    ->end()
                    ->scalarPrototype()->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
