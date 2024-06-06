<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Profile;
use App\Entity\User;
use libphonenumber\PhoneNumber;

interface ProfileManagerInterface
{
    public function getProfileByNickname(string $nickname): ?Profile;
    public function findByEmail(string $email): ?Profile;
    public function findByNickname(string $nickname): ?Profile;
    public function createHash(User $user, bool $hash = true, bool $enforceSecurity = true): User;
    public function findProfileByHash(?string $hash): ?User;
    public function changePhone(Profile $profile, PhoneNumber $newPhoneNumber): void;
    public function updateProfile(Profile $profile): void;
    public function verifyPhone(Profile $profile, PhoneNumber $phoneNumber): void;
    public function handlePhoneNumberFailedAttempt(Profile $profile): void;
    public function unverifyPhoneNumber(Profile $profile): void;
    public function isPhoneEditLimitReached(Profile $profile): bool;

    /** @param mixed $user */
    public function getProfile($user): ?Profile;

    /** @return Profile[] */
    public function findAllProfileWithEmptyDescriptionAndNotAnonymous(int $param = 14): array;
}
