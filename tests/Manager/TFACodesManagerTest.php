<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\GoogleAuthenticatorEntry;
use App\Entity\User;
use App\Manager\TFACodesManager;
use App\Manager\TwoFactorManagerInterface;
use DateTimeImmutable;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PragmaRX\Random\Random;

class TFACodesManagerTest extends TestCase
{
    public function testIsDownloadCodesLimitReached(): void
    {
        $limit = 10;
        $user = $this->createUser();
        $em = $this->mockEntityManager();
        $now = new DateTimeImmutable('2022-09-15');
        $twoFactorManager = $this->mockTwoFactorManager($user, 9, $now);
        $manager = new TFACodesManager($twoFactorManager, $em);
        $this->assertFalse($manager->isDownloadCodesLimitReached($user, $limit, $now->modify('+2 days')));
        $now = new DateTimeImmutable('2022-09-20');
        $twoFactorManager = $this->mockTwoFactorManager($user, 10, $now);
        $manager = new TFACodesManager($twoFactorManager, $em);
        $this->assertTrue($manager->isDownloadCodesLimitReached($user, $limit, $now->modify('+2 days')));
        $now = new DateTimeImmutable('2022-09-30');
        $twoFactorManager = $this->mockTwoFactorManager($user, 10, $now);
        $manager = new TFACodesManager($twoFactorManager, $em);
        $this->assertFalse($manager->isDownloadCodesLimitReached($user, $limit, $now->modify('+2 days')));
    }
    
    public function testDownloadBackupCode(): void
    {
        $user = $this->createUser();
        $em = $this->createMock(EntityManagerInterface::class);
        $twoFactorManager = $this->mockTwoFactorManager();
        $manager = new TFACodesManager($twoFactorManager, $em);
        $this->assertIsArray($manager->downloadBackupCode($user));
    }

    public function testGenerateBackupCodesFileName(): void
    {
        $user = $this->createUser();
        $em = $this->createMock(EntityManagerInterface::class);
        $twoFactorManager = $this->mockTwoFactorManager();
        $manager = new TFACodesManager($twoFactorManager, $em);
        $regex = '/^((backup-codes)-('. $user->getUsername() .')-(((0|1)[0-9])|2[0-3])-([0-5][0-9])-(0[1-9]|[1-2][0-9]|3[0-1])-(0[1-9]|1[0-2])-([0-9]{4})(.txt))$/i';
        $this->assertEquals(1, preg_match($regex, $manager->generateBackupCodesFileName($user)));
    }

    private function createUser(string $username = 'tester'): User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        $user->method('getUsername')->willReturn($username);

        return $user;
    }

    private function mockEntityManager(?ObjectRepository $repository = null): EntityManagerInterface
    {
        /** @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);

        return $entityManager;
    }

    private function mockTwoFactorManager(
        ?User $user = null,
        int $backupCodesDownloadAttemps = 0,
        ?\DateTimeImmutable $lastDownload = null
    ): TwoFactorManagerInterface {
        $twoFactorManager = $this->createMock(TwoFactorManagerInterface::class);
        $twoFactorManager->method('generateBackupCodes')->willReturnCallback(
            function () {
                $codes = [];

                for ($i = 0; $i < 5; $i++) {
                    $codes[] = (new Random())->size(12)->get();
                }

                return $codes;
            }
        );

        $twoFactorManager->method('getGoogleAuthEntry')->willReturnCallback(
            function (int $userId) use ($user, $backupCodesDownloadAttemps, $lastDownload) {
                $googleAuthEntry = new GoogleAuthenticatorEntry();
                $googleAuthEntry->setUser($user);
                $googleAuthEntry->setBackupCodesDownloads($backupCodesDownloadAttemps);
                $googleAuthEntry->setLastdownloadBackupDate($lastDownload);

                return $googleAuthEntry;
            }
        );
        
        return $twoFactorManager;
    }
}
