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

use FOS\UserBundle\Mailer\TwigSwiftMailer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Swift_Mailer;
use Swift_Transport_NullTransport;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class TwigSwiftMailerTest extends TestCase
{
    /**
     * @dataProvider goodEmailProvider
     */
    public function testSendConfirmationEmailMessageWithGoodEmails($emailAddress): void
    {
        $mailer = $this->getTwigSwiftMailer();
        $mailer->sendConfirmationEmailMessage($this->getUser($emailAddress));

        self::assertTrue(true);
    }

    /**
     * @dataProvider badEmailProvider
     */
    public function testSendConfirmationEmailMessageWithBadEmails($emailAddress): void
    {
        $this->expectException(\Swift_RfcComplianceException::class);
        $mailer = $this->getTwigSwiftMailer();
        $mailer->sendConfirmationEmailMessage($this->getUser($emailAddress));
    }

    /**
     * @dataProvider goodEmailProvider
     */
    public function testSendResettingEmailMessageWithGoodEmails($emailAddress): void
    {
        $mailer = $this->getTwigSwiftMailer();
        $mailer->sendResettingEmailMessage($this->getUser($emailAddress));

        self::assertTrue(true);
    }

    /**
     * @dataProvider badEmailProvider
     */
    public function testSendResettingEmailMessageWithBadEmails($emailAddress): void
    {
        $this->expectException(\Swift_RfcComplianceException::class);
        $mailer = $this->getTwigSwiftMailer();
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

    private function getTwigSwiftMailer(): TwigSwiftMailer
    {
        return new TwigSwiftMailer(
            new Swift_Mailer(
                new Swift_Transport_NullTransport(
                    $this->getMockBuilder('Swift_Events_EventDispatcher')->getMock()
                )
            ),
            $this->getMockBuilder(UrlGeneratorInterface::class)->getMock(),
            [
                'template' => [
                    'confirmation' => 'foo',
                    'resetting' => 'foo',
                ],
                'from_email' => [
                    'confirmation' => 'foo@example.com',
                    'resetting' => 'foo@example.com',
                ],
            ],
            $this->getTwigEnvironment()
        );
    }

    private function getTwigEnvironment(): Environment
    {
        return new Environment(new ArrayLoader(['foo' => <<<'TWIG'
{% block subject 'foo' %}

{% block body_text %}Test{% endblock %}

TWIG
        ]));
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
