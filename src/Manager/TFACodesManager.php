<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class TFACodesManager implements TFACodesManagerInterface
{
    private EntityManagerInterface $entityManager;

    private TwoFactorManagerInterface $twoFactorManager;
    
    public function __construct(
        TwoFactorManagerInterface $twoFactorManager,
        EntityManagerInterface $entityManager
    ) {
        $this->twoFactorManager = $twoFactorManager;
        $this->entityManager = $entityManager;
    }

    public function downloadBackupCode(User $user): array
    {
        $backupCodes = $this->twoFactorManager->generateBackupCodes();
        $user->setGoogleAuthenticatorBackupCodes($backupCodes);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $backupCodes;
    }

    public function handleDownloadCodeSuccess(User $user): void
    {
        $googleAuthEntry = $this->twoFactorManager->getGoogleAuthEntry($user->getId());
        $googleAuthEntry->setLastdownloadBackupDate(new DateTimeImmutable());
        $googleAuthEntry->incrementBackupCodesDownloadCount();
        $this->entityManager->persist($googleAuthEntry);
        $this->entityManager->flush();
    }

    public function getBackupCodes(User $user): array
    {
        $googleAuthEntry = $this->twoFactorManager->getGoogleAuthEntry($user->getId());
        
        return $googleAuthEntry->getBackupCodes();
    }
    
    public function generateBackupCodesFile(User $user, bool $regenerate): array
    {
        /** @var string $userAgent */
        $userAgent = $_SERVER['HTTP_USER_AGENT'];

        $lineBreak = preg_match('/Windows/i', $userAgent)
            ? "\r\n"
            : "\n";

        $backupCodes = $regenerate
            ? $this->downloadBackupCode($user)
            : $this->getBackupCodes($user);

        $content = implode($lineBreak, $backupCodes);
    
        return [
            'name' => $this->generateBackupCodesFileName($user),
            'file' => $content,
        ];
    }

    public function isDownloadCodesLimitReached(User $user, int $limit, ?DateTimeImmutable $currentDate): bool
    {
        $googleAuthEntry = $this->twoFactorManager->getGoogleAuthEntry($user->getId());
        $currentCount = $googleAuthEntry->getBackupCodesDownloads();
        $lastDate = $googleAuthEntry->getLastdownloadBackupDate();
        
        if ($lastDate && $limit <= $currentCount) {
            $currentMonth = $currentDate->format('Y, m');
            $lastMonth = $lastDate->format('Y, m');
            
            if ($currentMonth <= $lastMonth) {
                return true;
            }
            
            $googleAuthEntry->resetBackupCodesDownloadCount();
        }

        return false;
    }

    public function generateBackupCodesFileName(User $user): string
    {
        $name = $user->getUsername();
        $time = date("H-i-d-m-Y");

        return "backup-codes-{$name}-{$time}.txt";
    }
}
