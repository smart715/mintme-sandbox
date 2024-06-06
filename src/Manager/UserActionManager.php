<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\User;
use App\Entity\UserAction;
use App\Repository\UserActionRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserActionManager implements UserActionManagerInterface
{
    private UserActionRepository $repository;
    private EntityManagerInterface $em;

    public function __construct(UserActionRepository $repository, EntityManagerInterface $em)
    {
        $this->repository = $repository;
        $this->em = $em;
    }

    public function getRepository(): UserActionRepository
    {
        return $this->repository;
    }


    public function getCountByUserAtDate(User $user, string $action, \DateTimeImmutable $date): int
    {
        return $this->repository->getCountByUserAtDate($user, $action, $date);
    }

    public function getById(int $id): ?UserAction
    {
        return $this->repository->find($id);
    }

    public function createUserAction(User $user, string $action): void
    {
        $userLimit = new UserAction();
        $userLimit->setAction($action)->setUser($user);
        $this->em->persist($userLimit);
        $this->em->flush();
    }
}
