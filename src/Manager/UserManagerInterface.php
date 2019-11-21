<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Entity\UserToken;

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
}
