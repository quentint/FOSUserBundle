<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\UserBundle\Tests\Security;

use FOS\UserBundle\Security\LoginManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Http\RememberMe\RememberMeServicesInterface;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface;

class LoginManagerTest extends TestCase
{
    public function testLogInUserWithRequestStack(): void
    {
        $loginManager = $this->createLoginManager();
        $loginManager->logInUser('main', $this->mockUser());
    }

    public function testLogInUserWithRememberMeAndRequestStack(): void
    {
        $response = $this->getMockBuilder(Response::class)->getMock();

        $loginManager = $this->createLoginManager($response);
        $loginManager->logInUser('main', $this->mockUser(), $response);
    }

    private function createLoginManager(Response $response = null): LoginManager
    {
        $tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)->getMock();

        $tokenStorage
            ->expects(self::once())
            ->method('setToken')
            ->with(self::isInstanceOf(TokenInterface::class));

        $userChecker = $this->getMockBuilder(UserCheckerInterface::class)->getMock();
        $userChecker
            ->expects(self::once())
            ->method('checkPreAuth')
            ->with(self::isInstanceOf('FOS\UserBundle\Model\UserInterface'));

        $request = $this->getMockBuilder(Request::class)->getMock();

        $sessionStrategy = $this->getMockBuilder(SessionAuthenticationStrategyInterface::class)->getMock();
        $sessionStrategy
            ->expects(self::once())
            ->method('onAuthentication')
            ->with($request, self::isInstanceOf(TokenInterface::class));

        $requestStack = $this->getMockBuilder(RequestStack::class)->getMock();
        $requestStack
            ->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $rememberMe = null;
        if (null !== $response) {
            $rememberMe = $this->getMockBuilder(RememberMeServicesInterface::class)->getMock();
            $rememberMe
                ->expects(self::once())
                ->method('loginSuccess')
                ->with($request, $response, self::isInstanceOf(TokenInterface::class));
        }

        return new LoginManager($tokenStorage, $userChecker, $sessionStrategy, $requestStack, $rememberMe);
    }

    /**
     * @return mixed
     */
    private function mockUser()
    {
        $user = $this->getMockBuilder('FOS\UserBundle\Model\UserInterface')->getMock();
        $user
            ->expects(self::once())
            ->method('getRoles')
            ->willReturn(['ROLE_USER']);

        return $user;
    }
}
