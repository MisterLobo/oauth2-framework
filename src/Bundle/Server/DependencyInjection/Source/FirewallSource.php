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

namespace OAuth2Framework\Bundle\Server\DependencyInjection\Source;

use Fluent\PhpConfigFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class FirewallSource extends ActionableSource
{
    /**
     * {@inheritdoc}
     */
    protected function name(): string
    {
        return 'firewall';
    }

    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        $loader = new PhpConfigFileLoader($container, new FileLocator(__DIR__.'/../../Resources/config'));
        $loader->load('security.php');
    }
}
