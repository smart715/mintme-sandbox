<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\User;

interface UserManagerInterface extends \FOS\UserBundle\Model\UserManagerInterface
{
    public function find(int $id): ?User;
    public function findByReferralCode(string $code): ?User;
    public function getUserToken(int $token, array $userIds): array;
}
