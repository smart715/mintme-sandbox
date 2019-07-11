<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Profile;
use App\Entity\Token\Token;
use App\Entity\User;

interface ProfileManagerInterface
{
    public function getProfileByPageUrl(string $pageUrl): ?Profile;
    public function findByEmail(string $email): ?Profile;
    public function generatePageUrl(Profile $profile): ?string;
    public function createHash(User $user, bool $hash = true, bool $enforceSecurity = true): User;
    public function findProfileByHash(?string $hash): ?User;

    /**
     * @param mixed $user
     */
    public function getProfile($user): ?Profile;
}
