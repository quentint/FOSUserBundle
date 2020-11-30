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

use FOS\UserBundle\Command\CreateUserCommand;
use FOS\UserBundle\Util\UserManipulator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;

class CreateUserCommandTest extends TestCase
{
    public function testExecute(): void
    {
        $commandTester = $this->createCommandTester($this->getManipulator('user', 'pass', 'email', true, false));
        $exitCode = $commandTester->execute([
            'username' => 'user',
            'email' => 'email',
            'password' => 'pass',
        ], [
            'decorated' => false,
            'interactive' => false,
        ]);

        self::assertSame(0, $exitCode, 'Returns 0 in case of success');
        self::assertMatchesRegularExpression('/Created user user/', $commandTester->getDisplay());
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
            ->willReturn('email');

        $helper->expects(self::at(2))
            ->method('ask')
            ->willReturn('pass');

        $application->getHelperSet()->set($helper, 'question');

        $commandTester = $this->createCommandTester(
            $this->getManipulator('user', 'pass', 'email', true, false), $application
        );
        $exitCode = $commandTester->execute([], [
            'decorated' => false,
            'interactive' => true,
        ]);

        self::assertSame(0, $exitCode, 'Returns 0 in case of success');
        self::assertMatchesRegularExpression('/Created user user/', $commandTester->getDisplay());
    }

    private function createCommandTester(UserManipulator $manipulator, Application $application = null): CommandTester
    {
        if (null === $application) {
            $application = new Application();
        }

        $application->setAutoExit(false);

        $command = new CreateUserCommand($manipulator);

        $application->add($command);

        return new CommandTester($application->find('fos:user:create'));
    }

    /**
     * @param $username
     * @param $password
     * @param $email
     * @param $active
     * @param $superadmin
     *
     * @return mixed
     */
    private function getManipulator($username, $password, $email, $active, $superadmin)
    {
        $manipulator = $this->getMockBuilder(UserManipulator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $manipulator
            ->expects(self::once())
            ->method('create')
            ->with($username, $password, $email, $active, $superadmin)
        ;

        return $manipulator;
    }
}
