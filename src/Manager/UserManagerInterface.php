<?php

namespace App\Manager;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

interface UserManagerInterface extends \FOS\UserBundle\Model\UserManagerInterface
{
    public function find(int $id): ?User;
    public function createUserReferral(EntityManagerInterface $entityManager, int $user, ?string $referralCode): ?User;
    public function getReferencesTotal(int $userId): ?int;
}
