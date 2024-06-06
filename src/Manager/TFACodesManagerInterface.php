<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\User;

interface TFACodesManagerInterface
{
    public function isDownloadCodesLimitReached(User $user, int $limit, ?\DateTimeImmutable $currentDate): bool;
    public function generateBackupCodesFile(User $user, bool $regenerate): array;
    public function generateBackupCodesFileName(User $user): string;
    public function downloadBackupCode(User $user): array;
    public function handleDownloadCodeSuccess(User $user): void;
    public function getBackupCodes(User $user): array;
}
