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

use FOS\UserBundle\Model\User;
use FOS\UserBundle\Model\UserManagerInterface;
use FOS\UserBundle\Security\UserProvider;
use FOS\UserBundle\Tests\TestUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception as SecurityException;
use Symfony\Component\Security\Core\User\UserInterface;

class UserProviderTest extends TestCase
{
    private MockObject $userManager;
    private UserProvider $userProvider;

    protected function setUp(): void
    {
        $this->userManager = $this->getMockBuilder(UserManagerInterface::class)->getMock();
        $this->userProvider = new UserProvider($this->userManager);
    }

    public function testLoadUserByUsername(): void
    {
        $user = $this->getMockBuilder('FOS\UserBundle\Model\UserInterface')->getMock();
        $this->userManager->expects(self::once())
            ->method('findUserByUsername')
            ->with('foobar')
            ->willReturn($user);

        self::assertSame($user, $this->userProvider->loadUserByUsername('foobar'));
    }

    public function testLoadUserByInvalidUsername(): void
    {
        $this->expectException(SecurityException\UsernameNotFoundException::class);
        $this->userManager->expects(self::once())
            ->method('findUserByUsername')
            ->with('foobar')
            ->willReturn(null);

        $this->userProvider->loadUserByUsername('foobar');
    }

    public function testRefreshUserBy(): void
    {
        $user = $this->getMockBuilder(User::class)
                    ->setMethods(['getId'])
                    ->getMock();

        $user->expects(self::once())
            ->method('getId')
            ->willReturn('123');

        $refreshedUser = $this->getMockBuilder('FOS\UserBundle\Model\UserInterface')->getMock();
        $this->userManager->expects(self::once())
            ->method('findUserBy')
            ->with(['id' => '123'])
            ->willReturn($refreshedUser);

        $this->userManager->expects(self::atLeastOnce())
            ->method('getClass')
            ->willReturn(get_class($user));

        self::assertSame($refreshedUser, $this->userProvider->refreshUser($user));
    }

    public function testRefreshDeleted(): void
    {
        $this->expectException(SecurityException\UsernameNotFoundException::class);
        $user = $this->getMockForAbstractClass(User::class);
        $this->userManager->expects(self::once())
            ->method('findUserBy')
            ->willReturn(null);

        $this->userManager->expects(self::atLeastOnce())
            ->method('getClass')
            ->willReturn(get_class($user));

        $this->userProvider->refreshUser($user);
    }

    public function testRefreshInvalidUser(): void
    {
        $this->expectException(SecurityException\UnsupportedUserException::class);
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $this->userManager
            ->method('getClass')
            ->willReturn(get_class($user));

        $this->userProvider->refreshUser($user);
    }

    public function testRefreshInvalidUserClass(): void
    {
        $this->expectException(SecurityException\UnsupportedUserException::class);
        $user = $this->getMockBuilder(User::class)->getMock();
        $providedUser = $this->getMockBuilder(TestUser::class)->getMock();

        $this->userManager->expects(self::atLeastOnce())
            ->method('getClass')
            ->willReturn(get_class($user));

        $this->userProvider->refreshUser($providedUser);
    }
}
