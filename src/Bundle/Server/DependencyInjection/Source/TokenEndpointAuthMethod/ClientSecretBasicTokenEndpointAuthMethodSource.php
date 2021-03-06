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

namespace OAuth2Framework\Bundle\Server\DependencyInjection\Source\TokenEndpointAuthMethod;

use Fluent\PhpConfigFileLoader;
use OAuth2Framework\Bundle\Server\DependencyInjection\Source\ArraySource;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class ClientSecretBasicTokenEndpointAuthMethodSource extends ArraySource
{
    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        $container->setParameter($path.'.realm', $config['realm']);
        $container->setParameter($path.'.secret_lifetime', $config['secret_lifetime']);

        $loader = new PhpConfigFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/token_endpoint_auth_method'));
        $loader->load('client_secret_basic.php');
    }

    /**
     * {@inheritdoc}
     */
    protected function name(): string
    {
        return 'client_secret_basic';
    }

    /**
     * {@inheritdoc}
     */
    protected function continueConfiguration(NodeDefinition $node)
    {
        parent::continueConfiguration($node);
        $node
            ->children()
                ->scalarNode('realm')->isRequired()->end()
                ->integerNode('secret_lifetime')->defaultValue(60 * 60 * 24 * 14)->min(0)->end()
            ->end();
    }
}
