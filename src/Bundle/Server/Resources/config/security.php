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

use OAuth2Framework\Bundle\Server\Security\Authentication\Provider\OAuth2Provider;
use OAuth2Framework\Bundle\Server\Security\Firewall\OAuth2Listener;
use OAuth2Framework\Bundle\Server\Security\EntryPoint\OAuth2EntryPoint;
use OAuth2Framework\Bundle\Server\Annotation\AnnotationDriver;
use OAuth2Framework\Bundle\Server\Annotation\Checker;
use function Fluent\autowire;

return [
    OAuth2Provider::class => autowire()
        ->private(),

    OAuth2Listener::class => autowire()
        ->private(),

    OAuth2EntryPoint::class => autowire()
        ->private(),

    AnnotationDriver::class => autowire()
        ->tag('kernel.event_listener', ['event' => 'kernel.controller', 'method' => 'onKernelController'])
        ->private(),

    Checker\ClientIdChecker::class => autowire()
        ->private()
        ->tag('oauth2_server.security.annotation_checker'),

    Checker\ResourceOwnerIdChecker::class => autowire()
        ->private()
        ->tag('oauth2_server.security.annotation_checker'),

    Checker\ScopeChecker::class => autowire()
        ->private()
        ->tag('oauth2_server.security.annotation_checker'),
];
