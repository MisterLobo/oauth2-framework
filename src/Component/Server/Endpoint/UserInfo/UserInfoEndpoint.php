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

namespace OAuth2Framework\Component\Server\Endpoint\UserInfo;

use Http\Message\MessageFactory;
use Interop\Http\Server\RequestHandlerInterface;
use Interop\Http\Server\MiddlewareInterface;
use Jose\Component\Core\JWKSet;
use Jose\Component\Encryption\JWEBuilder;
use Jose\Component\Signature\JWSBuilder;
use OAuth2Framework\Component\Server\Model\AccessToken\AccessToken;
use OAuth2Framework\Component\Server\Model\Client\Client;
use OAuth2Framework\Component\Server\Model\Client\ClientRepositoryInterface;
use OAuth2Framework\Component\Server\Model\IdToken\IdTokenBuilderFactory;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountInterface;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountRepositoryInterface;
use OAuth2Framework\Component\Server\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;
use Psr\Http\Message\ServerRequestInterface;

final class UserInfoEndpoint implements MiddlewareInterface
{
    /**
     * @var JWKSet|null
     */
    private $signatureKeys = null;

    /**
     * @var JWSBuilder|null
     */
    private $jwsBuilder = null;

    /**
     * @var JWSBuilder|null
     */
    private $jweBuilder = null;

    /**
     * @var ClientRepositoryInterface
     */
    private $clientRepository;

    /**
     * @var UserAccountRepositoryInterface
     */
    private $userAccountRepository;

    /**
     * @var MessageFactory
     */
    private $messageFactory;

    /**
     * @var IdTokenBuilderFactory
     */
    private $idTokenBuilderFactory;

    /**
     * UserInfoEndpoint constructor.
     *
     * @param IdTokenBuilderFactory          $idTokenBuilderFactory
     * @param ClientRepositoryInterface      $clientRepository
     * @param UserAccountRepositoryInterface $userAccountRepository
     * @param MessageFactory                 $messageFactory
     */
    public function __construct(IdTokenBuilderFactory $idTokenBuilderFactory, ClientRepositoryInterface $clientRepository, UserAccountRepositoryInterface $userAccountRepository, MessageFactory $messageFactory)
    {
        $this->idTokenBuilderFactory = $idTokenBuilderFactory;
        $this->clientRepository = $clientRepository;
        $this->userAccountRepository = $userAccountRepository;
        $this->messageFactory = $messageFactory;
    }

    /**
     * @param JWSBuilder $jwsBuilder
     * @param JWKSet     $signatureKeys
     */
    public function enableSignature(JWSBuilder $jwsBuilder, JWKSet $signatureKeys)
    {
        $this->jwsBuilder = $jwsBuilder;
        $this->signatureKeys = $signatureKeys;
    }

    /**
     * @param JWEBuilder $jweBuilder
     */
    public function enableEncryption(JWEBuilder $jweBuilder)
    {
        $this->jweBuilder = $jweBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $requestHandler)
    {
        /**
         * @var AccessToken
         */
        $accessToken = $request->getAttribute('access_token');

        $this->checkScope($accessToken);
        $this->checkRedirectUri($accessToken);

        $client = $this->getClient($accessToken);
        $user = $this->getUserAccount($accessToken);

        $idToken = $this->buildUserinfoContent($client, $user, $accessToken, $isJwt);

        $response = $this->messageFactory->createResponse();
        $response->getBody()->write($idToken);
        $headers = ['Content-Type' => sprintf('application/%s; charset=UTF-8', $isJwt ? 'jwt' : 'json'), 'Cache-Control' => 'no-cache, no-store, max-age=0, must-revalidate, private', 'Pragma' => 'no-cache'];
        foreach ($headers as $k => $v) {
            $response = $response->withHeader($k, $v);
        }

        return $response;
    }

    /**
     * @param Client               $client
     * @param UserAccountInterface $userAccount
     * @param AccessToken          $accessToken
     * @param bool|null            $isJwt
     *
     * @return string
     */
    private function buildUserinfoContent(Client $client, UserAccountInterface $userAccount, AccessToken $accessToken, ? bool &$isJwt): string
    {
        $isJwt = false;
        $requestedClaims = $this->getEndpointClaims($accessToken);
        $idTokenBuilder = $this->idTokenBuilderFactory->createBuilder($client, $userAccount, $accessToken->getMetadata('redirect_uri'));

        if ($client->has('userinfo_signed_response_alg') && null !== $this->jwsBuilder) {
            $isJwt = true;
            $signatureAlgorithm = $client->get('userinfo_signed_response_alg');
            $idTokenBuilder = $idTokenBuilder->withSignature($this->jwsBuilder, $this->signatureKeys, $signatureAlgorithm);
        }
        if ($client->has('userinfo_encrypted_response_alg') && $client->has('userinfo_encrypted_response_enc') && null !== $this->jweBuilder) {
            $isJwt = true;
            $keyEncryptionAlgorithm = $client->get('userinfo_encrypted_response_alg');
            $contentEncryptionAlgorithm = $client->get('userinfo_encrypted_response_enc');
            $idTokenBuilder = $idTokenBuilder->withEncryption($this->jweBuilder, $keyEncryptionAlgorithm, $contentEncryptionAlgorithm);
        }
        $idTokenBuilder = $idTokenBuilder->withAccessToken($accessToken);
        $idTokenBuilder = $idTokenBuilder->withRequestedClaims($requestedClaims);
        $idToken = $idTokenBuilder->build();

        return $idToken;
    }

    /**
     * @param AccessToken $accessToken
     *
     * @return array
     */
    private function getEndpointClaims(AccessToken $accessToken): array
    {
        if (!$accessToken->hasMetadata('requested_claims')) {
            return [];
        }

        $requested_claims = $accessToken->getMetadata('requested_claims');
        if (true === array_key_exists('userinfo', $requested_claims)) {
            return $requested_claims['userinfo'];
        }

        return [];
    }

    /**
     * @param AccessToken $accessToken
     *
     * @throws OAuth2Exception
     *
     * @return Client
     */
    private function getClient(AccessToken $accessToken): Client
    {
        $clientId = $accessToken->getClientId();
        if (null === $clientId || null === $client = $this->clientRepository->find($clientId)) {
            throw new OAuth2Exception(400, ['error' => OAuth2ResponseFactoryManager::ERROR_INVALID_REQUEST, 'error_description' => 'Unable to find the client.']);
        }

        return $client;
    }

    /**
     * @param AccessToken $accessToken
     *
     * @throws OAuth2Exception
     *
     * @return UserAccountInterface
     */
    private function getUserAccount(AccessToken $accessToken): UserAccountInterface
    {
        $userAccountId = $accessToken->getResourceOwnerId();
        if (!$userAccountId instanceof UserAccountId || null === $userAccount = $this->userAccountRepository->findUserAccount($userAccountId)) {
            throw new OAuth2Exception(400, ['error' => OAuth2ResponseFactoryManager::ERROR_INVALID_REQUEST, 'error_description' => 'Unable to find the resource owner.']);
        }

        return $userAccount;
    }

    /**
     * @param AccessToken $accessToken
     *
     * @throws OAuth2Exception
     */
    private function checkRedirectUri(AccessToken $accessToken)
    {
        if (!$accessToken->hasMetadata('redirect_uri')) {
            throw new OAuth2Exception(400, ['error' => OAuth2ResponseFactoryManager::ERROR_INVALID_TOKEN, 'error_description' => 'The access token has not been issued through the authorization endpoint and cannot be used.']);
        }
    }

    /**
     * @param AccessToken $accessToken
     *
     * @throws OAuth2Exception
     */
    private function checkScope(AccessToken $accessToken)
    {
        if (!$accessToken->hasScope('openid')) {
            throw new OAuth2Exception(400, ['error' => OAuth2ResponseFactoryManager::ERROR_INVALID_TOKEN, 'error_description' => 'The access token does not contain the \'openid\' scope.']);
        }
    }
}
