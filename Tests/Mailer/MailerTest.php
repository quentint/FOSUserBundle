<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\UserBundle\Tests\Mailer;

use FOS\UserBundle\Mailer\Mailer;
use FOS\UserBundle\Mailer\TemplateInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Swift_Mailer;
use Swift_Transport_NullTransport;

class MailerTest extends TestCase
{
    /**
     * @dataProvider goodEmailProvider
     */
    public function testSendConfirmationEmailMessageWithGoodEmails($emailAddress): void
    {
        $mailer = $this->getMailer();
        $mailer->sendConfirmationEmailMessage($this->getUser($emailAddress));

        self::assertTrue(true);
    }

    /**
     * @dataProvider badEmailProvider
     */
    public function testSendConfirmationEmailMessageWithBadEmails($emailAddress): void
    {
        $this->expectException(\Swift_RfcComplianceException::class);
        $mailer = $this->getMailer();
        $mailer->sendConfirmationEmailMessage($this->getUser($emailAddress));
    }

    /**
     * @dataProvider goodEmailProvider
     */
    public function testSendResettingEmailMessageWithGoodEmails($emailAddress): void
    {
        $mailer = $this->getMailer();
        $mailer->sendResettingEmailMessage($this->getUser($emailAddress));

        self::assertTrue(true);
    }

    /**
     * @dataProvider badEmailProvider
     */
    public function testSendResettingEmailMessageWithBadEmails($emailAddress): void
    {
        $this->expectException(\Swift_RfcComplianceException::class);
        $mailer = $this->getMailer();
        $mailer->sendResettingEmailMessage($this->getUser($emailAddress));
    }

    public function goodEmailProvider(): array
    {
        return [
            ['foo@example.com'],
            ['foo@example.co.uk'],
            [$this->getEmailAddressValueObject('foo@example.com')],
            [$this->getEmailAddressValueObject('foo@example.co.uk')],
        ];
    }

    public function badEmailProvider(): array
    {
        return [
            ['foo'],
            [$this->getEmailAddressValueObject('foo')],
        ];
    }

    private function getMailer(): Mailer
    {
        return new Mailer(
            new Swift_Mailer(
                new Swift_Transport_NullTransport(
                    $this->getMockBuilder('\Swift_Events_EventDispatcher')->getMock()
                )
            ),
            $this->getMockBuilder('Symfony\Component\Routing\Generator\UrlGeneratorInterface')->getMock(),
            [
                'confirmation.template' => 'foo',
                'resetting.template' => 'foo',
                'from_email' => [
                    'confirmation' => 'foo@example.com',
                    'resetting' => 'foo@example.com',
                ],
            ],
            $this->getTemplating()
        );
    }

    private function getTemplating(): MockObject
    {
        $templating = $this->getMockBuilder(TemplateInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        return $templating;
    }

    private function getUser($emailAddress): MockObject
    {
        $user = $this->getMockBuilder('FOS\UserBundle\Model\UserInterface')->getMock();
        $user->method('getEmail')
            ->willReturn($emailAddress)
        ;

        return $user;
    }

    private function getEmailAddressValueObject($emailAddressAsString): MockObject
    {
        $emailAddress = $this->getMockBuilder('EmailAddress')
           ->setMethods(['__toString'])
           ->getMock();

        $emailAddress->method('__toString')
            ->willReturn($emailAddressAsString)
        ;

        return $emailAddress;
    }
}
