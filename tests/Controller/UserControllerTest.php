<?php declare(strict_types = 1);

namespace App\Tests\Controller;

use App\Entity\GoogleAuthenticatorEntry;
use App\Entity\User;
use App\Manager\TwoFactorManagerInterface;

class UserControllerTest extends WebTestCase
{
    public function testChangePassword(): void
    {
        $this->register($this->client);
        $this->client->request('GET', self::LOCALHOST . '/settings');
        $this->client->submitForm(
            'Save',
            [
                'app_user_change_password[current_password]' => self::DEFAULT_USER_PASS,
                'app_user_change_password[plainPassword]' => 'NewFoo123',
            ],
            'POST',
            [
                '_with_csrf' => false,
            ]
        );

        $this->assertContains('The password has been changed.', $this->client->getResponse()->getContent());
    }

    public function testEnable2fa(): void
    {
        $email = $this->register($this->client);
        $this->client->request('GET', self::LOCALHOST . '/settings/2fa');

        /** @var User $user */
        $user = $this->em->getRepository(User::class)->findOneBy([
            'email' => $email,
        ]);

        $this->client->submitForm(
            'Verify Code',
            [
                'two_factor[code]' => $user->getTrustedTokenVersion(),
            ],
            'POST',
            [
                '_with_csrf' => false,
            ]
        );

        $res = $this->client->getResponse()->getContent();

        $this->assertContains('Congratulations! You have enabled two-factor authentication!', $res);
        $this->assertContains('Downloading backups codes...', $res);
    }

    public function testDisable2fa(): void
    {
        $email = $this->register($this->client);
        $this->turnOn2FA($email);

        $this->client->request('GET', self::LOCALHOST . '/settings/2fa');

        /** @var User $user */
        $user = $this->em->getRepository(User::class)->findOneBy([
            'email' => $email,
        ]);

        $this->client->submitForm(
            'Verify Code',
            [
                'two_factor[code]' => $user->getGoogleAuthenticatorBackupCodes()[0],
            ],
            'POST',
            [
                '_with_csrf' => false,
            ]
        );

        $this->client->followRedirect();

        $this->assertContains(
            'You have disabled two-factor authentication!',
            $this->client->getResponse()->getContent()
        );
    }

    public function testGenerateBackupCodes(): void
    {
        $email = $this->register($this->client);
        $this->turnOn2FA($email);

        $this->client->request('GET', self::LOCALHOST . '/settings/2fa/backupcodes/generate');

        $this->client->followRedirect();

        $this->assertContains(
            'Downloading backup codes...',
            $this->client->getResponse()->getContent()
        );
    }

    public function testReferralProgram(): void
    {
        $email = $this->register($this->client);

        /** @var User $user */
        $user = $this->em->getRepository(User::class)->findOneBy([
            'email' => $email,
        ]);

        $this->client->request('GET', '/referral-program');

        $this->assertContains(
            self::LOCALHOST . '/invite/' . $user->getReferralCode(),
            $this->client->getResponse()->getContent()
        );
    }

    // todo fix referral count the revert
    public function estRegisterReferral(): void
    {
        $email = $this->register($this->client);

        /** @var User $user */
         $user = $this->em->getRepository(User::class)->findOneBy([
             'email' => $email,
         ]);

        $this->client->request('GET', self::LOCALHOST . '/referral-program');
        $crawler1 = $this->client->getCrawler();

        $this->registerReferral($user->getReferralCode());
        $this->registerReferral($user->getReferralCode());

        $this->client->request('GET', self::LOCALHOST . '/referral-program');
        $crawler2 = $this->client->getCrawler();

        $this->assertEquals(
            1,
            $crawler1->filter('#referral-link-text + div:contains("Referrals:") span:contains("0")')->count()
        );

        $this->assertEquals(
            1,
            $crawler2->filter('#referral-link-text + div:contains("Referrals:") span:contains("2")')->count()
        );
    }

    private function registerReferral(string $code): void
    {
        $fooClient = self::createClient();
        $fooEmail = $this->generateEmail();

        $fooClient->request('GET', self::LOCALHOST . '/invite/' . $code);
        $fooClient->followRedirect();
        $fooClient->submitForm(
            'Sign Up',
            [
                'fos_user_registration_form[email]' => $fooEmail,
                'fos_user_registration_form[nickname]' => $this->generateString(),
                'fos_user_registration_form[plainPassword]' => self::DEFAULT_USER_PASS,
            ],
            'POST',
            [
                '_with_csrf' => false,
            ]
        );
    }

    private function turnOn2FA(string $email): array
    {
        /** @var User $user */
        $user = $this->em->getRepository(User::class)->findOneBy([
            'email' => $email,
        ]);
        /** @var TwoFactorManagerInterface $twoFactorManager */
        $twoFactorManager = self::$container->get(TwoFactorManagerInterface::class);
        $backupCodes = $twoFactorManager->generateBackupCodes();
        $googleAuth = new GoogleAuthenticatorEntry();
        $googleAuth->setSecret('BAJVK6CKOJQHAK7YIHAC6JXJ6PS45VZND2J5M5SGEOUFW5EC5VMQ');
        $googleAuth->setUser($user);
        $googleAuth->setBackupCodes($backupCodes);
        $this->em->persist($googleAuth);
        $this->em->flush();

        return $backupCodes;
    }
}
