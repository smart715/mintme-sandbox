<?php

namespace App\Manager;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use FOS\UserBundle\Util\CanonicalFieldsUpdater;
use FOS\UserBundle\Util\PasswordUpdaterInterface;

class UserManager extends \FOS\UserBundle\Doctrine\UserManager implements UserManagerInterface
{
    /** @var UserRepository */
    private $userRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        PasswordUpdaterInterface $passwordUpdater,
        CanonicalFieldsUpdater $fieldsUpdater,
        ObjectManager $objectManager
    ) {
        parent::__construct($passwordUpdater, $fieldsUpdater, $objectManager, $this->getClass());
        $this->userRepository = $entityManager->getRepository(User::class);
    }

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
        return $this->userRepository->findByEmail($email);
    }

    public function findByIds(array $userIds): array
    {
        return $this->userRepository->findByIds($userIds);
    }
}
