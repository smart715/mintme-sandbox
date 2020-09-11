<?php declare(strict_types = 1);

namespace App\Tests\Controller\API;

use App\Entity\GoogleAuthenticatorEntry;
use App\Entity\PendingTokenWithdraw;
use App\Entity\PendingWithdraw;
use App\Entity\User;
use App\Manager\TwoFactorManagerInterface;
use App\Tests\Controller\WebTestCase;

class WalletControllerTest extends WebTestCase
{
    public function testGetDepositAddressesForCrypto(): void
    {
        $this->register($this->client);
        $this->client->request('GET', '/api/wallet/addresses');
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('WEB', $res);
        $this->assertArrayHasKey('BTC', $res);
        $this->assertArrayHasKey('TOK', $res);
        $this->assertNotEquals('', $res['WEB']);
        $this->assertNotEquals('', $res['BTC']);
        $this->assertNotEquals('', $res['TOK']);
    }

    public function testWithdrawCrypto(): void
    {
        $email = $this->register($this->client);
        $this->sendWeb($email);
        $backupCodes = $this->turnOn2FA($email);

        $this->client->request('GET', '/api/wallet/addresses');
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);

        $this->client->request('POST', '/api/wallet/withdraw', [
            'crypto' => 'WEB',
            'amount' => '10',
            'address' => $res['WEB'],
            'code' => $backupCodes[0],
        ]);

        /** @var PendingWithdraw $pendingWithdraw */
        $pendingWithdraw = $this->em->getRepository(PendingWithdraw::class)->findOneBy([
            'address' => $res['WEB'],
        ]);

        $this->assertEquals('WEB', $pendingWithdraw->getSymbol());
        $this->assertEquals($email, $pendingWithdraw->getUser()->getEmail());
        $this->assertEquals(10000000000000000000, $pendingWithdraw->getAmount()->getAmount()->getAmount());
    }

    public function testWithdrawToken(): void
    {
        $email = $this->register($this->client);
        $this->createProfile($this->client);
        $tokName = $this->createToken($this->client);
        $this->sendWeb($email);
        $backupCodes = $this->turnOn2FA($email);

        $this->client->request('GET', '/api/wallet/addresses');
        $res = json_decode((string)$this->client->getResponse()->getContent(), true);

        $this->client->request('POST', '/api/wallet/withdraw', [
            'crypto' => $tokName,
            'amount' => '10',
            'address' => $res['TOK'],
            'code' => $backupCodes[0],
        ]);

        /** @var PendingTokenWithdraw $pendingWithdraw */
        $pendingWithdraw = $this->em->getRepository(PendingTokenWithdraw::class)->findOneBy([
            'address' => $res['TOK'],
        ]);

        $this->assertEquals($tokName, $pendingWithdraw->getSymbol());
        $this->assertEquals($email, $pendingWithdraw->getUser()->getEmail());
        $this->assertEquals(10000000000000, $pendingWithdraw->getAmount()->getAmount()->getAmount());
    }

    public function testGetDepositFee(): void
    {
        $this->register($this->client);

        $this->client->request('GET', '/api/wallet/deposit/WEB/fee');

        $this->assertEquals(
            '0.004200000000000000',
            json_decode((string)$this->client->getResponse()->getContent(), true)
        );
    }

    public function testGetReferralBalance(): void
    {
        $email = $this->register($this->client);
        /** @var User $user */
        $user = $this->em->getRepository(User::class)->findOneBy([
            'email' => $email,
        ]);

        $fooClient = self::createClient();
        $fooEmail = $this->generateEmail();

        $fooClient->request('GET', 'https://localhost/invite/' . $user->getReferralCode());
        $fooClient->followRedirect();
        $fooClient->submitForm(
            'Sign Up',
            [
                'fos_user_registration_form[email]' => $fooEmail,
                'fos_user_registration_form[plainPassword]' => self::DEFAULT_USER_PASS,
            ],
            'POST',
            [
                '_with_csrf' => false,
            ]
        );
        $tokName = $this->createToken($fooClient);
        $this->sendWeb($fooEmail);

        $fooClient->request('POST', '/api/orders/WEB/'. $tokName . '/place-order', [
            'priceInput' => 1,
            'amountInput' => 10,
            'action' => 'sell',
        ]);

        $fooClient->request('POST', '/api/orders/WEB/'. $tokName . '/place-order', [
            'priceInput' => 1,
            'amountInput' => 10,
            'action' => 'buy',
        ]);

        $this->client->request('GET', '/api/wallet/referral');

        $this->assertEquals(
            '0.014970000000000000',
            json_decode((string)$this->client->getResponse()->getContent(), true)['balance']
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

    // todo test getDepositWithdrawHistory()
}
