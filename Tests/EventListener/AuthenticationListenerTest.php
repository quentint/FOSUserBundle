<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\UserBundle\Tests\EventListener;

use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\EventListener\AuthenticationListener;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Security\LoginManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticationListenerTest extends TestCase
{
    public const FIREWALL_NAME = 'foo';

    private EventDispatcherInterface $eventDispatcher;
    private FilterUserResponseEvent $event;
    private AuthenticationListener $listener;

    protected function setUp(): void
    {
        $user = $this->getMockBuilder('FOS\UserBundle\Model\UserInterface')->getMock();

        $response = $this->getMockBuilder(Response::class)->getMock();
        $request = $this->getMockBuilder(Request::class)->getMock();
        $this->event = new FilterUserResponseEvent($user, $request, $response);

        $this->eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')->getMock();
        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch');

        $loginManager = $this->getMockBuilder(LoginManagerInterface::class)->getMock();

        $this->listener = new AuthenticationListener($loginManager, self::FIREWALL_NAME);
    }

    public function testAuthenticate(): void
    {
        $this->listener->authenticate($this->event, FOSUserEvents::REGISTRATION_COMPLETED, $this->eventDispatcher);
    }
}
