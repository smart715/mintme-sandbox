<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\GoogleAuthenticatorEntry;
use App\Entity\User;

interface TwoFactorManagerInterface
{
    public function checkCode(User $user, string $code): bool;
    public function generateUrl(User $user): string;
    public function getGoogleAuthEntry(int $userId): GoogleAuthenticatorEntry;
    public function generateSecretCode(): string;
    public function generateBackupCodes(): array;
}
