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

namespace OAuth2Framework\Component\Server\Middleware;

use Interop\Http\Server\RequestHandlerInterface;
use Interop\Http\Server\MiddlewareInterface;
use OAuth2Framework\Component\Server\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;
use Psr\Http\Message\ServerRequestInterface;

final class OAuth2ResponseMiddleware implements MiddlewareInterface
{
    /**
     * @var OAuth2ResponseFactoryManager
     */
    private $auth2messageFactoryManager;

    /**
     * OAuth2ResponseMiddleware constructor.
     *
     * @param OAuth2ResponseFactoryManager $auth2messageFactoryManager
     */
    public function __construct(OAuth2ResponseFactoryManager $auth2messageFactoryManager)
    {
        $this->auth2messageFactoryManager = $auth2messageFactoryManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $requestHandler)
    {
        try {
            return $requestHandler->handle($request);
        } catch (OAuth2Exception $e) {
            $oauth2Response = $this->auth2messageFactoryManager->getResponse($e->getCode(), $e->getData());

            return $oauth2Response->getResponse();
        }
    }
}
