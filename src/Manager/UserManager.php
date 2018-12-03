<?php

namespace App\Manager;

use App\Entity\User;
use App\Repository\UserRepository;

class UserManager extends \FOS\UserBundle\Doctrine\UserManager implements UserManagerInterface
{

    public function find(int $id): ?User
    {
        return $this->getRepository()->find($id);
    }

    public function getRepository(): UserRepository
    {
        return parent::getRepository();
    }

    public function findByEmail(string $email): ?User
    {
        return $this->getRepository()->findByEmail($email);
    }

    public function findByIds(array $userIds): array
    {
        return $this->getRepository()->findByIds($userIds);
    }
}
