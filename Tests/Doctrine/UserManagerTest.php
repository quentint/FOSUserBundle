<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\UserBundle\Tests\Doctrine;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use FOS\UserBundle\Doctrine\UserManager;
use FOS\UserBundle\Model\User;
use FOS\UserBundle\Util\CanonicalFieldsUpdater;
use FOS\UserBundle\Util\PasswordUpdaterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserManagerTest extends TestCase
{
    public const USER_CLASS = DummyUser::class;
    protected UserManager $userManager;
    protected MockObject $om;
    protected MockObject $repository;

    protected function setUp(): void
    {
        $passwordUpdater = $this->getMockBuilder(PasswordUpdaterInterface::class)->getMock();
        $fieldsUpdater = $this->getMockBuilder(CanonicalFieldsUpdater::class)
            ->disableOriginalConstructor()
            ->getMock();

        $class = $this
            ->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->om = $this
            ->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->repository = $this->getMockBuilder(ObjectRepository::class)->getMock();

        $this->om
            ->method('getRepository')
            ->with(self::equalTo(static::USER_CLASS))
            ->willReturn($this->repository);

        $this->om
            ->method('getClassMetadata')
            ->with(self::equalTo(static::USER_CLASS))
            ->willReturn($class);

        $class
            ->method('getName')
            ->willReturn(static::USER_CLASS);

        $this->userManager = new UserManager($passwordUpdater, $fieldsUpdater, $this->om, static::USER_CLASS);
    }

    public function testDeleteUser(): void
    {
        $user = $this->getUser();
        $this->om->expects(self::once())->method('remove')->with(self::equalTo($user));
        $this->om->expects(self::once())->method('flush');

        $this->userManager->deleteUser($user);
    }

    public function testGetClass(): void
    {
        self::assertSame(static::USER_CLASS, $this->userManager->getClass());
    }

    public function testFindUserBy(): void
    {
        $crit = ['foo' => 'bar'];
        $this->repository->expects(self::once())->method('findOneBy')->with(self::equalTo($crit))->willReturn([]);

        $this->userManager->findUserBy($crit);
    }

    public function testFindUsers(): void
    {
        $this->repository->expects(self::once())->method('findAll')->willReturn([]);

        $this->userManager->findUsers();
    }

    public function testUpdateUser(): void
    {
        $user = $this->getUser();
        $this->om->expects(self::once())->method('persist')->with(self::equalTo($user));
        $this->om->expects(self::once())->method('flush');

        $this->userManager->updateUser($user);
    }

    /**
     * @return mixed
     */
    protected function getUser()
    {
        $userClass = static::USER_CLASS;

        return new $userClass();
    }
}

class DummyUser extends User
{
}
