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

namespace OAuth2Framework\Bundle\Server\DependencyInjection\Source\Endpoint;

use Jose\Bundle\JoseFramework\Helper\ConfigurationHelper;
use OAuth2Framework\Bundle\Server\DependencyInjection\Source\ActionableSource;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class AuthorizationEndpointRequestObjectSource extends ActionableSource
{
    /**
     * AuthorizationEndpointRequestObjectSource constructor.
     */
    public function __construct()
    {
        $this->addSubSource(new AuthorizationEndpointRequestObjectReferenceSource());
        $this->addSubSource(new AuthorizationEndpointRequestObjectEncryptionSource());
    }

    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        foreach ($config as $k => $v) {
            $container->setParameter($path.'.'.$k, $v);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function name(): string
    {
        return 'request_object';
    }

    /**
     * {@inheritdoc}
     */
    protected function continueConfiguration(NodeDefinition $node)
    {
        parent::continueConfiguration($node);
        $node
            ->children()
                ->arrayNode('signature_algorithms')
                    ->info('Supported signature algorithms.')
                    ->useAttributeAsKey('name')
                    ->prototype('scalar')->end()
                    ->treatNullLike([])
                ->end()
            ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(array $bundleConfig, string $path, ContainerBuilder $container)
    {
        parent::prepend($bundleConfig, $path, $container);
        $currentPath = $path.'['.$this->name().']';
        $accessor = PropertyAccess::createPropertyAccessor();
        $sourceConfig = $accessor->getValue($bundleConfig, $currentPath);
        if (true === $sourceConfig['enabled']) {
            $claim_checkers = ['exp', 'iat', 'nbf'/*'authorization_endpoint_aud'*/]; // FIXME
            $header_checkers = ['crit']; // FIXME
            ConfigurationHelper::addJWSLoader($container, $this->name(), $sourceConfig['signature_algorithms'], [], ['jws_compact'], false);
            ConfigurationHelper::addClaimChecker($container, $this->name(), $claim_checkers, false);
            if (true === $sourceConfig['encryption']['enabled']) {
                ConfigurationHelper::addJWELoader($container, $this->name(), $sourceConfig['encryption']['key_encryption_algorithms'], $sourceConfig['encryption']['content_encryption_algorithms'], ['DEF'], [], ['jwe_compact'], false);
            }
        }
    }
}
