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

namespace OAuth2Framework\Bundle\Server\Security\Firewall;

use OAuth2Framework\Bundle\Server\Security\Authentication\Token\OAuth2Token;
use OAuth2Framework\Component\Server\Model\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;
use OAuth2Framework\Component\Server\Security\AccessTokenHandlerManager;
use OAuth2Framework\Component\Server\TokenType\TokenTypeManager;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;

final class OAuth2Listener implements ListenerInterface
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var AuthenticationManagerInterface
     */
    private $authenticationManager;

    /**
     * @var TokenTypeManager
     */
    private $tokenTypeManager;

    /**
     * @var AccessTokenHandlerManager
     */
    private $accessTokenHandlerManager;

    /**
     * @var OAuth2ResponseFactoryManager
     */
    private $oauth2ResponseFactoryManager;

    /**
     * OAuth2Listener constructor.
     *
     * @param TokenStorageInterface          $tokenStorage
     * @param AuthenticationManagerInterface $authenticationManager
     * @param TokenTypeManager               $tokenTypeManager
     * @param AccessTokenHandlerManager      $accessTokenHandlerManager
     * @param OAuth2ResponseFactoryManager   $oauth2ResponseFactoryManager
     */
    public function __construct(TokenStorageInterface $tokenStorage,
                                AuthenticationManagerInterface $authenticationManager,
                                TokenTypeManager $tokenTypeManager,
                                AccessTokenHandlerManager $accessTokenHandlerManager,
                                OAuth2ResponseFactoryManager $oauth2ResponseFactoryManager
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->authenticationManager = $authenticationManager;
        $this->tokenTypeManager = $tokenTypeManager;
        $this->accessTokenHandlerManager = $accessTokenHandlerManager;
        $this->oauth2ResponseFactoryManager = $oauth2ResponseFactoryManager;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(GetResponseEvent $event)
    {
        $factory = new DiactorosFactory();
        $request = $factory->createRequest($event->getRequest());

        try {
            $additionalCredentialValues = [];
            $accessTokenId = $this->tokenTypeManager->findToken($request, $additionalCredentialValues);
            if (null === $accessTokenId) {
                return;
            }
        } catch (\Exception $e) {
            return;
        }

        try {
            $accessToken = $this->accessTokenHandlerManager->find(AccessTokenId::create($accessTokenId));
            if (null === $accessToken || true === $accessToken->isRevoked()) {
                throw new AuthenticationException('Invalid access token.');
            }

            $token = new OAuth2Token($accessToken);
            $result = $this->authenticationManager->authenticate($token);

            $this->tokenStorage->setToken($result);
        } catch (AuthenticationException $e) {
            if (null !== $e->getPrevious()) {
                $e = $e->getPrevious();
            }
            $oauth2Response = $this->oauth2ResponseFactoryManager->getResponse(401, ['error' => 'invalid_grant', 'error_description' => $e->getMessage()]);
            $response = $oauth2Response->getResponse();
            $factory = new HttpFoundationFactory();
            $event->setResponse($factory->createResponse($response));
        }
    }
}
