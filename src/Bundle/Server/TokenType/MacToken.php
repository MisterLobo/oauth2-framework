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

namespace OAuth2Framework\Bundle\Server\TokenType;

use Base64Url\Base64Url;
use OAuth2Framework\Component\Server\TokenType\MacToken as Base;

final class MacToken extends Base
{
    /**
     * @var int
     */
    private $minLength;

    /**
     * @var int
     */
    private $maxLength;

    /**
     * MacToken constructor.
     *
     * @param string $macAlgorithm
     * @param int    $timestampLifetime
     * @param int    $minLength
     * @param int    $maxLength
     */
    public function __construct(string $macAlgorithm, int $timestampLifetime, int $minLength, int $maxLength)
    {
        parent::__construct($macAlgorithm, $timestampLifetime);
        $this->minLength = $minLength;
        $this->maxLength = $maxLength;
    }

    /**
     * {@inheritdoc}
     */
    protected function generateMacKey(): string
    {
        return Base64Url::encode(random_bytes($this->getMacKeyLength()));
    }

    /**
     * @return int
     */
    private function getMacKeyLength(): int
    {
        return mt_rand($this->minLength, $this->maxLength);
    }
}
