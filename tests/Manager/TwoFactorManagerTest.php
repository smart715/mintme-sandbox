<?php

namespace App\Tests\Manager;

use App\Entity\GoogleAuthenticatorEntry;
use App\Entity\User;
use App\Manager\TwoFactorManager;
use App\OrmAdapter\OrmAdapterInterface;
use App\Repository\GoogleAuthenticatorEntryRepository;
use Doctrine\Common\Persistence\ObjectRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class TwoFactorManagerTest extends TestCase
{
    public function testCheckCode(): void
    {
        $form = $this->mockFormInterface(['code' => '1']);
        $session = $this->mockSession();
        $ormAdapter = $this->mockOrmAdapter();
        $googleAuth = $this->mockGoogleAuthenticatorInterface();
        $googleAuthEntry = $this->mockUser(['1', '2']);
        $manager = new TwoFactorManager($session, $ormAdapter, $googleAuth);
        $this->assertTrue($manager->checkCode($googleAuthEntry, $form));
        $manager = new TwoFactorManager($session, $ormAdapter, $googleAuth);
        $googleAuthEntry = $this->mockUser(['3', '2']);
        $this->assertFalse($manager->checkCode($googleAuthEntry, $form));
        $googleAuthEntry = $this->mockUser(['2', '2']);
        $googleAuth = $this->mockGoogleAuthenticatorInterface(true);
        $manager = new TwoFactorManager($session, $ormAdapter, $googleAuth);
        $this->assertTrue($manager->checkCode($googleAuthEntry, $form));
    }

    public function testGenerateBackUpCodes(): void
    {
        $session = $this->mockSession();
        $ormAdapter = $this->mockOrmAdapter();
        $googleAuth = $this->mockGoogleAuthenticatorInterface();
        $twoFactorManager = new TwoFactorManager($session, $ormAdapter, $googleAuth);
        $this->assertCount(5, $twoFactorManager->generateBackupCodes());
        $this->assertEquals(12, strlen($twoFactorManager->generateBackupCodes()[0]));
    }

    public function testGenerateSecretCode(): void
    {
        $session = $this->mockSession(true, '1');
        $ormAdapter = $this->mockOrmAdapter();
        $googleAuth = $this->mockGoogleAuthenticatorInterface(true, '', '2');
        $twoFactorManager = new TwoFactorManager($session, $ormAdapter, $googleAuth);
        $this->assertEquals('1', $twoFactorManager->generateSecretCode());
        $session = $this->mockSession(false, '1');
        $twoFactorManager = new TwoFactorManager($session, $ormAdapter, $googleAuth);
        $this->assertEquals('2', $twoFactorManager->generateSecretCode());
    }

    public function testGenerateUrl(): void
    {
        /** @var User|MockObject $user */
        $user = $this->createMock(User::class);
        $session = $this->mockSession();
        $ormAdapter = $this->mockOrmAdapter();
        $googleAuth = $this->mockGoogleAuthenticatorInterface(true, 'test');
        $twoFactorManager = new TwoFactorManager($session, $ormAdapter, $googleAuth);
        $this->assertEquals('test', $twoFactorManager->generateUrl($user));
    }

    public function testGetGoogleAuthEntry(): void
    {
        $session = $this->mockSession();
        $ormAdapter = $this->mockOrmAdapter($this->mockGoogleAuthRepo());
        $googleAuth = $this->mockGoogleAuthenticatorInterface();
        $twoFactorManager = new TwoFactorManager($session, $ormAdapter, $googleAuth);
        $instance = $twoFactorManager->getGoogleAuthEntry(1);
        $this->assertInstanceOf(GoogleAuthenticatorEntry::class, $instance);
    }

    private function mockFormInterface(array $array = []): FormInterface
    {
        /** @var FormInterface|MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->method('getData')->willReturn($array);
        return $form;
    }

    private function mockGoogleAuthenticatorInterface(
        bool $bool = false,
        string $url = '',
        string $secret = ''
    ): GoogleAuthenticatorInterface {
        /** @var GoogleAuthenticatorInterface|MockObject $googleAuth */
        $googleAuth = $this->createMock(GoogleAuthenticatorInterface::class);
        $googleAuth->method('getUrl')->willReturn($url);
        $googleAuth->method('checkCode')->willReturn($bool);
        $googleAuth->method('generateSecret')->willReturn($secret);
        return $googleAuth;
    }

    private function mockSession(bool $has = true, string $code = ''): SessionInterface
    {
        /** @var SessionInterface|MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $session->method('has')->willReturn($has);
        $session->method('get')->willReturn($code);
        return $session;
    }

    private function mockUser(array $backupCodes = []): User
    {
        /** @var User|MockObject $user */
        $user = $this->createMock(User::class);
        $user->method('getGoogleAuthenticatorBackupCodes')->willReturn($backupCodes);
        return $user;
    }

    private function mockOrmAdapter(?ObjectRepository $repository = null): OrmAdapterInterface
    {
        /** @var OrmAdapterInterface|MockObject $ormAdapter */
        $ormAdapter = $this->createMock(OrmAdapterInterface::class);
        $ormAdapter->method('getRepository')->willReturn($repository);
        return $ormAdapter;
    }

    private function mockGoogleAuthRepo(): GoogleAuthenticatorEntryRepository
    {
        return $this->createMock(GoogleAuthenticatorEntryRepository::class);
    }
}
