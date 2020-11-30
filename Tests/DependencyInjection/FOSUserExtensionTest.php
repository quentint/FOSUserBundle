<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\UserBundle\Tests\DependencyInjection;

use FOS\UserBundle\DependencyInjection\FOSUserExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Parser;

class FOSUserExtensionTest extends TestCase
{
    protected ?ContainerBuilder $configuration;

    protected function tearDown(): void
    {
        $this->configuration = null;
    }

    public function testUserLoadThrowsExceptionUnlessDatabaseDriverSet(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $loader = new FOSUserExtension();
        $config = $this->getEmptyConfig();
        unset($config['db_driver']);
        $loader->load([$config], new ContainerBuilder());
    }

    public function testUserLoadThrowsExceptionUnlessDatabaseDriverIsValid(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $loader = new FOSUserExtension();
        $config = $this->getEmptyConfig();
        $config['db_driver'] = 'foo';
        $loader->load([$config], new ContainerBuilder());
    }

    public function testUserLoadThrowsExceptionUnlessFirewallNameSet(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $loader = new FOSUserExtension();
        $config = $this->getEmptyConfig();
        unset($config['firewall_name']);
        $loader->load([$config], new ContainerBuilder());
    }

    public function testUserLoadThrowsExceptionUnlessGroupModelClassSet(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $loader = new FOSUserExtension();
        $config = $this->getFullConfig();
        unset($config['group']['group_class']);
        $loader->load([$config], new ContainerBuilder());
    }

    public function testUserLoadThrowsExceptionUnlessUserModelClassSet(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $loader = new FOSUserExtension();
        $config = $this->getEmptyConfig();
        unset($config['user_class']);
        $loader->load([$config], new ContainerBuilder());
    }

    public function testCustomDriverWithoutManager(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $loader = new FOSUserExtension();
        $config = $this->getEmptyConfig();
        $config['db_driver'] = 'custom';
        $loader->load([$config], new ContainerBuilder());
    }

    public function testCustomDriver(): void
    {
        $this->configuration = new ContainerBuilder();
        $loader = new FOSUserExtension();
        $config = $this->getEmptyConfig();
        $config['db_driver'] = 'custom';
        $config['service']['user_manager'] = 'acme.user_manager';
        $loader->load([$config], $this->configuration);

        $this->assertNotHasDefinition('fos_user.user_manager.default');
        $this->assertAlias('acme.user_manager', 'fos_user.user_manager');
        $this->assertParameter('custom', 'fos_user.storage');
    }

    public function testDisableRegistration(): void
    {
        $this->configuration = new ContainerBuilder();
        $loader = new FOSUserExtension();
        $config = $this->getEmptyConfig();
        $config['registration'] = false;
        $loader->load([$config], $this->configuration);
        $this->assertNotHasDefinition('fos_user.registration.form.factory');

        $mailer = $this->configuration->getDefinition('fos_user.mailer.default');
        $parameters = $this->configuration->getParameterBag()->resolveValue(
            $mailer->getArgument(2)
        );
        self::assertSame(
            [
                'confirmation' => ['no-registration@acme.com' => 'Acme Ltd'],
                'resetting' => ['admin@acme.org' => 'Acme Corp'],
            ],
            $parameters['from_email']
        );
    }

    public function testDisableResetting(): void
    {
        $this->configuration = new ContainerBuilder();
        $loader = new FOSUserExtension();
        $config = $this->getEmptyConfig();
        $config['resetting'] = false;
        $loader->load([$config], $this->configuration);
        $this->assertNotHasDefinition('fos_user.resetting.form.factory');

        $mailer = $this->configuration->getDefinition('fos_user.mailer.default');
        $parameters = $this->configuration->getParameterBag()->resolveValue(
            $mailer->getArgument(2)
        );
        self::assertSame(
            [
                'confirmation' => ['admin@acme.org' => 'Acme Corp'],
                'resetting' => ['no-resetting@acme.com' => 'Acme Ltd'],
            ],
            $parameters['from_email']
        );
    }

    public function testDisableProfile(): void
    {
        $this->configuration = new ContainerBuilder();
        $loader = new FOSUserExtension();
        $config = $this->getEmptyConfig();
        $config['profile'] = false;
        $loader->load([$config], $this->configuration);
        $this->assertNotHasDefinition('fos_user.profile.form.factory');
    }

    public function testDisableChangePassword(): void
    {
        $this->configuration = new ContainerBuilder();
        $loader = new FOSUserExtension();
        $config = $this->getEmptyConfig();
        $config['change_password'] = false;
        $loader->load([$config], $this->configuration);
        $this->assertNotHasDefinition('fos_user.change_password.form.factory');
    }

    /**
     * @dataProvider providerEmailsDisabledFeature
     */
    public function testEmailsDisabledFeature($testConfig, $registration, $resetting): void
    {
        $this->configuration = new ContainerBuilder();
        $loader = new FOSUserExtension();
        $config = $this->getEmptyConfig();
        $config = array_merge($config, $testConfig);
        $loader->load([$config], $this->configuration);

        $this->assertParameter($registration, 'fos_user.registration.confirmation.from_email');
        $this->assertParameter($resetting, 'fos_user.resetting.email.from_email');
    }

    public function providerEmailsDisabledFeature(): array
    {
        $configBothFeaturesDisabled = ['registration' => false, 'resetting' => false];
        $configResettingDisabled = ['resetting' => false];
        $configRegistrationDisabled = ['registration' => false];
        $configOverridenRegistrationEmail = [
            'registration' => [
                'confirmation' => [
                    'from_email' => ['address' => 'ltd@acme.com', 'sender_name' => 'Acme Ltd'],
                ],
            ],
        ];
        $configOverridenResettingEmail = [
            'resetting' => [
                'email' => [
                    'from_email' => ['address' => 'ltd@acme.com', 'sender_name' => 'Acme Ltd'],
                ],
            ],
        ];

        $default = ['admin@acme.org' => 'Acme Corp'];
        $overriden = ['ltd@acme.com' => 'Acme Ltd'];

        return [
            [$configBothFeaturesDisabled, ['no-registration@acme.com' => 'Acme Ltd'], ['no-resetting@acme.com' => 'Acme Ltd']],
            [$configResettingDisabled, $default, ['no-resetting@acme.com' => 'Acme Ltd']],
            [$configRegistrationDisabled, ['no-registration@acme.com' => 'Acme Ltd'], $default],
            [$configOverridenRegistrationEmail, $overriden, $default],
            [$configOverridenResettingEmail, $default, $overriden],
        ];
    }

    public function testUserLoadModelClassWithDefaults(): void
    {
        $this->createEmptyConfiguration();

        $this->assertParameter('Acme\MyBundle\Document\User', 'fos_user.model.user.class');
    }

    public function testUserLoadModelClass(): void
    {
        $this->createFullConfiguration();

        $this->assertParameter('Acme\MyBundle\Entity\User', 'fos_user.model.user.class');
    }

    public function testUserLoadManagerClassWithDefaults(): void
    {
        $this->createEmptyConfiguration();

        $this->assertParameter('mongodb', 'fos_user.storage');
        $this->assertParameter(null, 'fos_user.model_manager_name');
        $this->assertAlias('fos_user.user_manager.default', 'fos_user.user_manager');
        $this->assertNotHasDefinition('fos_user.group_manager');
    }

    public function testUserLoadManagerClass(): void
    {
        $this->createFullConfiguration();

        $this->assertParameter('orm', 'fos_user.storage');
        $this->assertParameter('custom', 'fos_user.model_manager_name');
        $this->assertAlias('acme_my.user_manager', 'fos_user.user_manager');
        $this->assertAlias('fos_user.group_manager.default', 'fos_user.group_manager');
    }

    public function testUserLoadFormClass(): void
    {
        $this->createFullConfiguration();

        $this->assertParameter('acme_my_profile', 'fos_user.profile.form.type');
        $this->assertParameter('acme_my_registration', 'fos_user.registration.form.type');
        $this->assertParameter('acme_my_group', 'fos_user.group.form.type');
        $this->assertParameter('acme_my_change_password', 'fos_user.change_password.form.type');
        $this->assertParameter('acme_my_resetting', 'fos_user.resetting.form.type');
    }

    public function testUserLoadFormNameWithDefaults(): void
    {
        $this->createEmptyConfiguration();

        $this->assertParameter('fos_user_profile_form', 'fos_user.profile.form.name');
        $this->assertParameter('fos_user_registration_form', 'fos_user.registration.form.name');
        $this->assertParameter('fos_user_change_password_form', 'fos_user.change_password.form.name');
        $this->assertParameter('fos_user_resetting_form', 'fos_user.resetting.form.name');
    }

    public function testUserLoadFormName(): void
    {
        $this->createFullConfiguration();

        $this->assertParameter('acme_profile_form', 'fos_user.profile.form.name');
        $this->assertParameter('acme_registration_form', 'fos_user.registration.form.name');
        $this->assertParameter('acme_group_form', 'fos_user.group.form.name');
        $this->assertParameter('acme_change_password_form', 'fos_user.change_password.form.name');
        $this->assertParameter('acme_resetting_form', 'fos_user.resetting.form.name');
    }

    public function testUserLoadFormServiceWithDefaults(): void
    {
        $this->createEmptyConfiguration();

        $this->assertHasDefinition('fos_user.profile.form.factory');
        $this->assertHasDefinition('fos_user.registration.form.factory');
        $this->assertNotHasDefinition('fos_user.group.form.factory');
        $this->assertHasDefinition('fos_user.change_password.form.factory');
        $this->assertHasDefinition('fos_user.resetting.form.factory');
    }

    public function testUserLoadFormService(): void
    {
        $this->createFullConfiguration();

        $this->assertHasDefinition('fos_user.profile.form.factory');
        $this->assertHasDefinition('fos_user.registration.form.factory');
        $this->assertHasDefinition('fos_user.group.form.factory');
        $this->assertHasDefinition('fos_user.change_password.form.factory');
        $this->assertHasDefinition('fos_user.resetting.form.factory');
    }

    public function testUserLoadConfirmationEmailWithDefaults(): void
    {
        $this->createEmptyConfiguration();

        $this->assertParameter(false, 'fos_user.registration.confirmation.enabled');
        $this->assertParameter(['admin@acme.org' => 'Acme Corp'], 'fos_user.registration.confirmation.from_email');
        $this->assertParameter('@FOSUser/Registration/email.txt.twig', 'fos_user.registration.confirmation.template');
        $this->assertParameter('@FOSUser/Resetting/email.txt.twig', 'fos_user.resetting.email.template');
        $this->assertParameter(['admin@acme.org' => 'Acme Corp'], 'fos_user.resetting.email.from_email');
        $this->assertParameter(86400, 'fos_user.resetting.token_ttl');
    }

    public function testUserLoadConfirmationEmail(): void
    {
        $this->createFullConfiguration();

        $this->assertParameter(true, 'fos_user.registration.confirmation.enabled');
        $this->assertParameter(['register@acme.org' => 'Acme Corp'], 'fos_user.registration.confirmation.from_email');
        $this->assertParameter('AcmeMyBundle:Registration:mail.txt.twig', 'fos_user.registration.confirmation.template');
        $this->assertParameter('AcmeMyBundle:Resetting:mail.txt.twig', 'fos_user.resetting.email.template');
        $this->assertParameter(['reset@acme.org' => 'Acme Corp'], 'fos_user.resetting.email.from_email');
        $this->assertParameter(7200, 'fos_user.resetting.retry_ttl');
    }

    public function testUserLoadUtilServiceWithDefaults(): void
    {
        $this->createEmptyConfiguration();

        $this->assertAlias('fos_user.mailer.default', 'fos_user.mailer');
        $this->assertAlias('fos_user.util.canonicalizer.default', 'fos_user.util.email_canonicalizer');
        $this->assertAlias('fos_user.util.canonicalizer.default', 'fos_user.util.username_canonicalizer');
    }

    public function testUserLoadUtilService(): void
    {
        $this->createFullConfiguration();

        $this->assertAlias('acme_my.mailer', 'fos_user.mailer');
        $this->assertAlias('acme_my.templating', 'fos_user.templating');
        $this->assertAlias('acme_my.email_canonicalizer', 'fos_user.util.email_canonicalizer');
        $this->assertAlias('acme_my.username_canonicalizer', 'fos_user.util.username_canonicalizer');
    }

    public function testUserLoadFlashesByDefault(): void
    {
        $this->createEmptyConfiguration();

        $this->assertHasDefinition('fos_user.listener.flash');
    }

    public function testUserLoadFlashesCanBeDisabled(): void
    {
        $this->createFullConfiguration();

        $this->assertNotHasDefinition('fos_user.listener.flash');
    }

    /**
     * @dataProvider userManagerSetFactoryProvider
     *
     * @param $dbDriver
     * @param $doctrineService
     */
    public function testUserManagerSetFactory($dbDriver, $doctrineService): void
    {
        $this->configuration = new ContainerBuilder();
        $loader = new FOSUserExtension();
        $config = $this->getEmptyConfig();
        $config['db_driver'] = $dbDriver;
        $loader->load([$config], $this->configuration);

        $definition = $this->configuration->getDefinition('fos_user.object_manager');

        $this->assertAlias($doctrineService, 'fos_user.doctrine_registry');

        if (method_exists($definition, 'getFactory')) {
            $factory = $definition->getFactory();

            self::assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $factory[0]);
            self::assertSame('fos_user.doctrine_registry', (string) $factory[0]);
            self::assertSame('getManager', $factory[1]);
        } else {
            self::assertSame('fos_user.doctrine_registry', $definition->getFactoryService());
            self::assertSame('getManager', $definition->getFactoryMethod());
        }
    }

    public function userManagerSetFactoryProvider(): array
    {
        return [
            ['orm', 'doctrine'],
            ['couchdb', 'doctrine_couchdb'],
            ['mongodb', 'doctrine_mongodb'],
        ];
    }

    protected function createEmptyConfiguration(): void
    {
        $this->configuration = new ContainerBuilder();
        $loader = new FOSUserExtension();
        $config = $this->getEmptyConfig();
        $loader->load([$config], $this->configuration);
        self::assertInstanceOf(ContainerBuilder::class, $this->configuration);
    }

    protected function createFullConfiguration(): void
    {
        $this->configuration = new ContainerBuilder();
        $loader = new FOSUserExtension();
        $config = $this->getFullConfig();
        $loader->load([$config], $this->configuration);
        self::assertInstanceOf(ContainerBuilder::class, $this->configuration);
    }

    protected function getEmptyConfig(): array
    {
        $yaml = <<<EOF
db_driver: mongodb
firewall_name: fos_user
user_class: Acme\MyBundle\Document\User
from_email:
    address: admin@acme.org
    sender_name: Acme Corp
EOF;
        $parser = new Parser();

        return $parser->parse($yaml);
    }

    /**
     * @return mixed
     */
    protected function getFullConfig()
    {
        $yaml = <<<EOF
db_driver: orm
firewall_name: fos_user
use_listener: true
use_flash_notifications: false
user_class: Acme\MyBundle\Entity\User
model_manager_name: custom
from_email:
    address: admin@acme.org
    sender_name: Acme Corp
profile:
    form:
        type: acme_my_profile
        name: acme_profile_form
        validation_groups: [acme_profile]
change_password:
    form:
        type: acme_my_change_password
        name: acme_change_password_form
        validation_groups: [acme_change_password]
registration:
    confirmation:
        from_email:
            address: register@acme.org
            sender_name: Acme Corp
        enabled: true
        template: AcmeMyBundle:Registration:mail.txt.twig
    form:
        type: acme_my_registration
        name: acme_registration_form
        validation_groups: [acme_registration]
resetting:
    retry_ttl: 7200
    token_ttl: 86400
    email:
        from_email:
            address: reset@acme.org
            sender_name: Acme Corp
        template: AcmeMyBundle:Resetting:mail.txt.twig
    form:
        type: acme_my_resetting
        name: acme_resetting_form
        validation_groups: [acme_resetting]
service:
    mailer: acme_my.mailer
    email_canonicalizer: acme_my.email_canonicalizer
    username_canonicalizer: acme_my.username_canonicalizer
    user_manager: acme_my.user_manager
    templating: acme_my.templating
group:
    group_class: Acme\MyBundle\Entity\Group
    form:
        type: acme_my_group
        name: acme_group_form
        validation_groups: [acme_group]
EOF;
        $parser = new Parser();

        return $parser->parse($yaml);
    }

    private function assertAlias(string $value, string $key): void
    {
        self::assertSame($value, (string) $this->configuration->getAlias($key), sprintf('%s alias is correct', $key));
    }

    /**
     * @param mixed $value
     */
    private function assertParameter($value, string $key): void
    {
        self::assertSame($value, $this->configuration->getParameter($key), sprintf('%s parameter is correct', $key));
    }

    private function assertHasDefinition(string $id): void
    {
        self::assertTrue(($this->configuration->hasDefinition($id) ?: $this->configuration->hasAlias($id)));
    }

    private function assertNotHasDefinition(string $id): void
    {
        self::assertFalse(($this->configuration->hasDefinition($id) ?: $this->configuration->hasAlias($id)));
    }
}
