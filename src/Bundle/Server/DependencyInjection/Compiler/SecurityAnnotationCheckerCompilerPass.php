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

use OAuth2Framework\Bundle\Server\Annotation\AnnotationDriver;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class SecurityAnnotationCheckerCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(AnnotationDriver::class)) {
            return;
        }

        $definition = $container->getDefinition(AnnotationDriver::class);
        $taggedServices = $container->findTaggedServiceIds('oauth2_server.security.annotation_checker');
        foreach ($taggedServices as $id => $attributes) {
            $definition->addMethodCall('addChecker', [new Reference($id)]);
        }
    }
}
