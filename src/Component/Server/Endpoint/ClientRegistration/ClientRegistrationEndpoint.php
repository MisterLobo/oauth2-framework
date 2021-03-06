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

namespace OAuth2Framework\Component\Server\Endpoint\ClientRegistration;

use Assert\Assertion;
use Http\Message\MessageFactory;
use Interop\Http\Server\RequestHandlerInterface;
use Interop\Http\Server\MiddlewareInterface;
use OAuth2Framework\Component\Server\Command\Client\CreateClientCommand;
use OAuth2Framework\Component\Server\DataTransporter;
use OAuth2Framework\Component\Server\Model\Client\Client;
use OAuth2Framework\Component\Server\Model\Client\ClientId;
use OAuth2Framework\Component\Server\Model\DataBag\DataBag;
use OAuth2Framework\Component\Server\Model\InitialAccessToken\InitialAccessToken;
use OAuth2Framework\Component\Server\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;
use SimpleBus\Message\Bus\MessageBus;

final class ClientRegistrationEndpoint implements MiddlewareInterface
{
    /**
     * @var MessageBus
     */
    private $messageBus;

    /**
     * @var MessageFactory
     */
    private $messageFactory;

    /**
     * ClientRegistrationEndpoint constructor.
     *
     * @param MessageFactory $messageFactory
     * @param MessageBus     $messageBus
     */
    public function __construct(MessageFactory $messageFactory, MessageBus $messageBus)
    {
        $this->messageFactory = $messageFactory;
        $this->messageBus = $messageBus;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $requestHandler = null): ResponseInterface
    {
        $this->checkRequest($request);
        $data = new DataTransporter();
        $initialAccessToken = $request->getAttribute('initial_access_token');

        try {
            if (null !== $initialAccessToken) {
                Assertion::isInstanceOf($initialAccessToken, InitialAccessToken::class, 'Initial Access Token is missing or invalid.');
                $userAccountId = $initialAccessToken->getUserAccountId();
            } else {
                $userAccountId = null;
            }
            $commandParameters = DataBag::createFromArray($request->getParsedBody() ?? []);
            // Allow custom client id generators
            $clientId = ClientId::create(Uuid::uuid4()->toString());
            $command = CreateClientCommand::create($clientId, $userAccountId, $commandParameters, $data);
            $this->messageBus->handle($command);
        } catch (\InvalidArgumentException $e) {
            throw new OAuth2Exception(400, ['error' => OAuth2ResponseFactoryManager::ERROR_INVALID_REQUEST, 'error_description' => $e->getMessage()]);
        }

        return $this->createResponse($data->getData());
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @throws OAuth2Exception
     */
    private function checkRequest(ServerRequestInterface $request)
    {
        if ('POST' !== $request->getMethod()) {
            throw new OAuth2Exception(
                405,
                [
                    'error' => OAuth2ResponseFactoryManager::ERROR_INVALID_REQUEST,
                    'error_description' => 'Unsupported method.',
                ]
            );
        }
    }

    /**
     * @param Client $client
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function createResponse(Client $client): ResponseInterface
    {
        $response = $this->messageFactory->createResponse(201);
        foreach (['Content-Type' => 'application/json', 'Cache-Control' => 'no-store', 'Pragma' => 'no-cache'] as $k => $v) {
            $response = $response->withHeader($k, $v);
        }
        $response->getBody()->write(json_encode($client->all(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $response;
    }
}
