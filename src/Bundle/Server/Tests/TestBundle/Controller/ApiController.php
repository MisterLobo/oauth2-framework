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

namespace OAuth2Framework\Bundle\Server\Tests\TestBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use OAuth2Framework\Bundle\Server\Annotation\OAuth2;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @OAuth2()
 * @Route("/api")
 */
final class ApiController extends Controller
{
    /**
     * @param string $name
     *
     * @return Response
     *
     * @Route("/hello/{name}", name="api_hello")
     */
    public function serviceAction(string $name)
    {
        return new JsonResponse(['name' => $name, 'message' => sprintf('Hello %s!', $name)]);
    }

    /**
     * @return Response
     *
     * @OAuth2(scope="profile openid")
     * @Route("/scope", name="api_scope")
     */
    public function scopeProtectionAction()
    {
        return new JsonResponse(['name' => 'I am protected by scope', 'message' => 'Hello!']);
    }
}
