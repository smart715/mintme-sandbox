<?php declare(strict_types = 1);

namespace App\Tests\Controller;

use App\Entity\GoogleAuthenticatorEntry;
use App\Entity\PendingTokenWithdraw;
use App\Entity\PendingWithdraw;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Manager\TwoFactorManagerInterface;

class WalletControllerFix extends WebTestCase
{
    public function testWithdrawConfirmCrypto(): void
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

        $this->assertNotNull($pendingWithdraw);

        $this->client->request(
            'GET',
            '/wallet/withdraw/' . $pendingWithdraw->getHash(),
            [],
            [],
            ['HTTPS' => true]
        );

        $this->client->followRedirect();

        /** @var PendingWithdraw|null $pendingWithdraw */
        $pendingWithdraw = $this->em->getRepository(PendingWithdraw::class)->findOneBy([
            'address' => $res['WEB'],
        ]);

        $this->assertNull($pendingWithdraw);
    }

    public function testWithdrawConfirmToken(): void
    {
        $email = $this->register($this->client);
        $tokName = $this->createToken($this->client);
        $this->sendWeb($email);
        $backupCodes = $this->turnOn2FA($email);

        /** @var Token $token */
        $token = $this->em->getRepository(Token::class)->findOneBy([
            'name' => $tokName,
        ]);
        $token->setAddress('0xaa');
        $this->em->persist($token);
        $this->em->flush();

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

        $this->assertNotNull($pendingWithdraw);

        $this->client->request(
            'GET',
            '/wallet/withdraw/' . $pendingWithdraw->getHash(),
            [],
            [],
            ['HTTPS' => true]
        );

        $this->client->followRedirect();

        /** @var PendingTokenWithdraw|null $pendingWithdraw */
        $pendingWithdraw = $this->em->getRepository(PendingTokenWithdraw::class)->findOneBy([
            'address' => $res['TOK'],
        ]);

        $this->assertNull($pendingWithdraw);
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
