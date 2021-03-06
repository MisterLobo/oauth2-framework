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

namespace OAuth2Framework\Bundle\Server\DependencyInjection\Compiler;

use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class ResponseFactoryCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(OAuth2ResponseFactoryManager::class)) {
            return;
        }

        $definition = $container->getDefinition(OAuth2ResponseFactoryManager::class);
        $taggedServices = $container->findTaggedServiceIds('oauth2_server_response_factory');
        foreach ($taggedServices as $id => $attributes) {
            $definition->addMethodCall('addResponseFactory', [new Reference($id)]);
        }
    }
}
