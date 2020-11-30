<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\UserBundle\Tests\Model;

use FOS\UserBundle\Model\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUsername(): void
    {
        $user = $this->getUser();
        self::assertNull($user->getUsername());

        $user->setUsername('tony');
        self::assertSame('tony', $user->getUsername());
    }

    public function testEmail(): void
    {
        $user = $this->getUser();
        self::assertNull($user->getEmail());

        $user->setEmail('tony@mail.org');
        self::assertSame('tony@mail.org', $user->getEmail());
    }

    public function testIsPasswordRequestNonExpired(): void
    {
        $user = $this->getUser();
        $passwordRequestedAt = new \DateTime('-10 seconds');

        $user->setPasswordRequestedAt($passwordRequestedAt);

        self::assertSame($passwordRequestedAt, $user->getPasswordRequestedAt());
        self::assertTrue($user->isPasswordRequestNonExpired(15));
        self::assertFalse($user->isPasswordRequestNonExpired(5));
    }

    public function testIsPasswordRequestAtCleared(): void
    {
        $user = $this->getUser();
        $passwordRequestedAt = new \DateTime('-10 seconds');

        $user->setPasswordRequestedAt($passwordRequestedAt);
        $user->setPasswordRequestedAt(null);

        self::assertFalse($user->isPasswordRequestNonExpired(15));
        self::assertFalse($user->isPasswordRequestNonExpired(5));
    }

    public function testTrueHasRole(): void
    {
        $user = $this->getUser();
        $defaultrole = User::ROLE_DEFAULT;
        $newrole = 'ROLE_X';
        self::assertTrue($user->hasRole($defaultrole));
        $user->addRole($defaultrole);
        self::assertTrue($user->hasRole($defaultrole));
        $user->addRole($newrole);
        self::assertTrue($user->hasRole($newrole));
    }

    public function testFalseHasRole(): void
    {
        $user = $this->getUser();
        $newrole = 'ROLE_X';
        self::assertFalse($user->hasRole($newrole));
        $user->addRole($newrole);
        self::assertTrue($user->hasRole($newrole));
    }

    public function testIsEqualTo(): void
    {
        $user = $this->getUser();
        self::assertTrue($user->isEqualTo($user));
        self::assertFalse($user->isEqualTo($this->getMockBuilder('FOS\UserBundle\Model\UserInterface')->getMock()));

        $user2 = $this->getUser();
        $user2->setPassword('secret');
        self::assertFalse($user->isEqualTo($user2));

        $user3 = $this->getUser();
        $user3->setSalt('pepper');
        self::assertFalse($user->isEqualTo($user3));

        $user4 = $this->getUser();
        $user4->setUsername('f00b4r');
        self::assertFalse($user->isEqualTo($user4));
    }

    protected function getUser(): User
    {
        return $this->getMockForAbstractClass(User::class);
    }
}
