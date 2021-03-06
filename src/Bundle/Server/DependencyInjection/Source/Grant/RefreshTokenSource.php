<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Bundle\Server\DependencyInjection\Source\Grant;

use Fluent\PhpConfigFileLoader;
use OAuth2Framework\Bundle\Server\DependencyInjection\Source\ActionableSource;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class RefreshTokenSource extends ActionableSource
{
    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        foreach (['min_length', 'max_length', 'lifetime'] as $k) {
            $container->setParameter($path.'.'.$k, $config[$k]);
        }
        $container->setAlias($path.'.event_store', $config['event_store']);

        $loader = new PhpConfigFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/grant'));
        $loader->load('refresh_token.php');
    }

    /**
     * {@inheritdoc}
     */
    protected function name(): string
    {
        return 'refresh_token';
    }

    /**
     * {@inheritdoc}
     */
    protected function continueConfiguration(NodeDefinition $node)
    {
        parent::continueConfiguration($node);
        $node
            ->validate()
                ->ifTrue(function ($config) {
                    return true === $config['enabled'] && empty($config['event_store']);
                })
                ->thenInvalid('The option "event_store" must be set.')
            ->end()
            ->validate()
                ->ifTrue(function ($config) {
                    return true === $config['enabled'] && $config['max_length'] < $config['min_length'];
                })
                ->thenInvalid('The option "max_length" must be greater than "min_length".')
            ->end()
            ->children()
                ->integerNode('min_length')->defaultValue(50)->min(0)->end()
                ->integerNode('max_length')->defaultValue(100)->min(1)->end()
                ->integerNode('lifetime')->defaultValue(60 * 60 * 24 * 7)->min(1)->end()
                ->scalarNode('event_store')->defaultNull()->end()
            ->end();
    }
}
