<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\UserBundle\Tests\Util;

use FOS\UserBundle\Tests\TestUser;
use FOS\UserBundle\Util\PasswordUpdater;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

class PasswordUpdaterTest extends TestCase
{
    private PasswordUpdater $updater;
    private MockObject $encoderFactory;

    protected function setUp(): void
    {
        $this->encoderFactory = $this->getMockBuilder(EncoderFactoryInterface::class)->getMock();

        $this->updater = new PasswordUpdater($this->encoderFactory);
    }

    public function testUpdatePassword(): void
    {
        $encoder = $this->getMockPasswordEncoder();
        $user = new TestUser();
        $user->setPlainPassword('password');

        $this->encoderFactory->expects(self::once())
            ->method('getEncoder')
            ->with($user)
            ->willReturn($encoder);

        $encoder->expects(self::once())
            ->method('encodePassword')
            ->with('password', self::logicalOr(self::isType('string'), self::isNull()))
            ->willReturn('encodedPassword');

        $this->updater->hashPassword($user);
        self::assertSame('encodedPassword', $user->getPassword(), '->updatePassword() sets encoded password');
        self::assertNull($user->getPlainPassword(), '->updatePassword() erases credentials');
    }

    public function testDoesNotUpdateWithoutPlainPassword(): void
    {
        $user = new TestUser();
        $user->setPassword('hash');

        $user->setPlainPassword('');

        $this->updater->hashPassword($user);
        self::assertSame('hash', $user->getPassword());
    }

    private function getMockPasswordEncoder(): MockObject
    {
        return $this->getMockBuilder(PasswordEncoderInterface::class)->disableOriginalConstructor()->getMock();
    }
}
