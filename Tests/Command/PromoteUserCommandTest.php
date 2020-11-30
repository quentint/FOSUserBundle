<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\UserBundle\Tests\Command;

use FOS\UserBundle\Command\PromoteUserCommand;
use FOS\UserBundle\Util\UserManipulator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;

class PromoteUserCommandTest extends TestCase
{
    public function testExecute(): void
    {
        $commandTester = $this->createCommandTester($this->getManipulator('user', 'role', false));
        $exitCode = $commandTester->execute([
            'username' => 'user',
            'role' => 'role',
        ], [
            'decorated' => false,
            'interactive' => false,
        ]);

        self::assertSame(0, $exitCode, 'Returns 0 in case of success');
        self::assertMatchesRegularExpression('/Role "role" has been added to user "user"/', $commandTester->getDisplay());
    }

    public function testExecuteInteractiveWithQuestionHelper(): void
    {
        $application = new Application();

        $helper = $this->getMockBuilder(QuestionHelper::class)
            ->setMethods(['ask'])
            ->getMock();

        $helper->expects(self::at(0))
            ->method('ask')
            ->willReturn('user');
        $helper->expects(self::at(1))
            ->method('ask')
            ->willReturn('role');

        $application->getHelperSet()->set($helper, 'question');

        $commandTester = $this->createCommandTester($this->getManipulator('user', 'role', false), $application);
        $exitCode = $commandTester->execute([], [
            'decorated' => false,
            'interactive' => true,
        ]);

        self::assertSame(0, $exitCode, 'Returns 0 in case of success');
        self::assertMatchesRegularExpression('/Role "role" has been added to user "user"/', $commandTester->getDisplay());
    }

    private function createCommandTester(UserManipulator $manipulator, Application $application = null): CommandTester
    {
        if (null === $application) {
            $application = new Application();
        }

        $application->setAutoExit(false);

        $command = new PromoteUserCommand($manipulator);

        $application->add($command);

        return new CommandTester($application->find('fos:user:promote'));
    }

    /**
     * @param $username
     * @param $role
     * @param $super
     *
     * @return mixed
     */
    private function getManipulator($username, $role, $super)
    {
        $manipulator = $this->getMockBuilder(UserManipulator::class)
            ->disableOriginalConstructor()
            ->getMock();

        if ($super) {
            $manipulator
                ->expects(self::once())
                ->method('promote')
                ->with($username)
                ->willReturn(true)
            ;
        } else {
            $manipulator
                ->expects(self::once())
                ->method('addRole')
                ->with($username, $role)
                ->willReturn(true)
            ;
        }

        return $manipulator;
    }
}
