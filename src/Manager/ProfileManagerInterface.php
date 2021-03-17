<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Profile;
use App\Entity\User;

interface ProfileManagerInterface
{
    public function getProfileByNickname(string $nickname): ?Profile;
    public function findByEmail(string $email): ?Profile;
    public function findByNickname(string $nickname): ?Profile;
    public function createHash(User $user, bool $hash = true, bool $enforceSecurity = true): User;
    public function findProfileByHash(?string $hash): ?User;

    /**
     * @param mixed $user
     */
    public function getProfile($user): ?Profile;
}
