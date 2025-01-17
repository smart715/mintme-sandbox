<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Entity\UserChangeEmailRequest;
use App\Entity\UserToken;
use App\Repository\UserRepository;

interface UserManagerInterface extends \FOS\UserBundle\Model\UserManagerInterface
{
    public function find(int $id): ?User;
    public function findByReferralCode(string $code): ?User;

    /**
     * @param Token $token
     * @param int[] $userIds
     * @return UserToken[]
     */
    public function getUserToken(Token $token, array $userIds): array;

    /**
     * @param Crypto $crypto
     * @param int[] $userIds
     * @return UserToken[]
     */
    public function getUserCrypto(Crypto $crypto, array $userIds): array;
    public function getRepository(): UserRepository;
    public function getUsersByDomains(array $domains): ?array;
    public function findByDomain(string $domain): array;
    public function findByDiscordId(int $discordId): ?User;
    public function checkExistCanonicalEmail(string $email): bool;
    public function sendMintmeExchangeMail(User $user): void;
    public function changeEmail(user $user, string $newEmail): void;
    public function verifyNewEmail(user $user): ?UserChangeEmailRequest;
    public function getUserChangeEmailRequest(User $user): ?UserChangeEmailRequest;
    public function saveSessionId(User $user, string $sessionId): void;
    public function isSessionIdValid(User $user, string $sessionId): bool;
}
