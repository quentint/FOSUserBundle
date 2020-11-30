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
use FOS\UserBundle\Security\EmailUserProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception as SecurityException;
use Symfony\Component\Security\Core\User\UserInterface;

class EmailUserProviderTest extends TestCase
{
    private MockObject $userManager;
    private EmailUserProvider $userProvider;

    protected function setUp(): void
    {
        $this->userManager = $this->getMockBuilder(UserManagerInterface::class)->getMock();
        $this->userProvider = new EmailUserProvider($this->userManager);
    }

    public function testLoadUserByUsername(): void
    {
        $user = $this->getMockBuilder('FOS\UserBundle\Model\UserInterface')->getMock();
        $this->userManager->expects(self::once())
            ->method('findUserByUsernameOrEmail')
            ->with('foobar')
            ->willReturn($user);

        self::assertSame($user, $this->userProvider->loadUserByUsername('foobar'));
    }

    public function testLoadUserByInvalidUsername(): void
    {
        $this->expectException(SecurityException\UsernameNotFoundException::class);
        $this->userManager->expects(self::once())
            ->method('findUserByUsernameOrEmail')
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

    public function testRefreshInvalidUser(): void
    {
        $this->expectException(SecurityException\UnsupportedUserException::class);
        $user = $this->getMockBuilder(UserInterface::class)->getMock();

        $this->userProvider->refreshUser($user);
    }
}
