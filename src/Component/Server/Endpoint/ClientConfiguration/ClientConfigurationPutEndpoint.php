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

namespace OAuth2Framework\Component\Server\Endpoint\ClientConfiguration;

use Http\Message\MessageFactory;
use Interop\Http\Server\RequestHandlerInterface;
use Interop\Http\Server\MiddlewareInterface;
use OAuth2Framework\Component\Server\Command\Client\UpdateClientCommand;
use OAuth2Framework\Component\Server\DataTransporter;
use OAuth2Framework\Component\Server\Model\DataBag\DataBag;
use OAuth2Framework\Component\Server\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;
use Psr\Http\Message\ServerRequestInterface;
use SimpleBus\Message\Bus\MessageBus;

final class ClientConfigurationPutEndpoint implements MiddlewareInterface
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
     * ClientConfigurationPutEndpoint constructor.
     *
     * @param MessageBus     $messageBus
     * @param MessageFactory $messageFactory
     */
    public function __construct(MessageBus $messageBus, MessageFactory $messageFactory)
    {
        $this->messageBus = $messageBus;
        $this->messageFactory = $messageFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $next)
    {
        $client = $request->getAttribute('client');

        $data = new DataTransporter();
        $command_parameters = DataBag::createFromArray($request->getParsedBody() ?? []);
        $command = UpdateClientCommand::create($client, $command_parameters, $data);

        try {
            $this->messageBus->handle($command);
        } catch (\InvalidArgumentException $e) {
            throw new OAuth2Exception(400, ['error' => OAuth2ResponseFactoryManager::ERROR_INVALID_REQUEST, 'error_description' => $e->getMessage()]);
        }

        $response = $this->messageFactory->createResponse();
        $response->getBody()->write(json_encode($data->getData()->all(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $headers = ['Content-Type' => 'application/json; charset=UTF-8', 'Cache-Control' => 'no-cache, no-store, max-age=0, must-revalidate, private', 'Pragma' => 'no-cache'];
        foreach ($headers as $k => $v) {
            $response = $response->withHeader($k, $v);
        }

        return $response;
    }
}
