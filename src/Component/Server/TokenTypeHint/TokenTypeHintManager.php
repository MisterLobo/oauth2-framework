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

namespace OAuth2Framework\Component\Server\TokenTypeHint;

final class TokenTypeHintManager
{
    /**
     * @var TokenTypeHintInterface[]
     */
    private $tokenTypeHints = [];

    /**
     * @return TokenTypeHintInterface[]
     */
    public function getTokenTypeHints(): array
    {
        return $this->tokenTypeHints;
    }

    /**
     * @param TokenTypeHintInterface $tokenTypeHint
     *
     * @return TokenTypeHintManager
     */
    public function add(TokenTypeHintInterface $tokenTypeHint): TokenTypeHintManager
    {
        $this->tokenTypeHints[$tokenTypeHint->hint()] = $tokenTypeHint;

        return $this;
    }
}
