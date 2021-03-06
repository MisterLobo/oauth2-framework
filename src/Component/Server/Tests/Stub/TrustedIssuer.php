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

namespace OAuth2Framework\Component\Server\Tests\Stub;

use Jose\Component\Core\JWKSet;
use OAuth2Framework\Component\Server\Model\TrustedIssuer\TrustedIssuerInterface;

final class TrustedIssuer implements TrustedIssuerInterface
{
    /**
     * @var string
     */
    private $issuerName;

    /**
     * @var JWKSet
     */
    private $publicKeys;

    /**
     * @var string[]
     */
    private $allowedAlgorithms;

    /**
     * TrustedIssuer constructor.
     *
     * @param string $issuerName
     * @param array  $allowedAlgorithms
     * @param JWKSet $publicKeys
     */
    public function __construct(string $issuerName, array $allowedAlgorithms, JWKSet $publicKeys)
    {
        $this->issuerName = $issuerName;
        $this->publicKeys = $publicKeys;
        $this->allowedAlgorithms = $allowedAlgorithms;
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return $this->issuerName;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedSignatureAlgorithms(): array
    {
        return $this->allowedAlgorithms;
    }

    /**
     * {@inheritdoc}
     */
    public function getSignatureKeys(): JWKSet
    {
        return $this->publicKeys;
    }
}
