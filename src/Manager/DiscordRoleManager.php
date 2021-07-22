<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\DiscordRole;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Repository\DiscordRoleRepository;
use Doctrine\ORM\EntityManagerInterface;

class DiscordRoleManager implements DiscordRoleManagerInterface
{
    private DiscordRoleRepository $repository;
    private BalanceHandlerInterface $balanceHandler;
    private TokenManagerInterface $tokenManager;
    private EntityManagerInterface $entityManager;

    public function __construct(
        DiscordRoleRepository $repository,
        BalanceHandlerInterface $balanceHandler,
        TokenManagerInterface $tokenManager,
        EntityManagerInterface $entityManager
    ) {
        $this->repository = $repository;
        $this->balanceHandler = $balanceHandler;
        $this->tokenManager = $tokenManager;
        $this->entityManager = $entityManager;
    }

    public function findRoleOfUser(User $user, Token $token): ?DiscordRole
    {
        $balanceResult = $this->balanceHandler->balance($user, $token);
        $balanceResult = $this->tokenManager->getRealBalance($token, $balanceResult, $user);

        return $this->repository->findByTokenAndAmount($token, $balanceResult->getAvailable());
    }

    public function removeRole(DiscordRole $role, bool $andFlush = true): void
    {
        $this->entityManager->remove($role);

        if ($andFlush) {
            $this->entityManager->flush();
        }
    }

    public function removeAllRoles(Token $token): void
    {
        foreach ($token->getDiscordRoles()->toArray() as $role) {
            $this->removeRole($role, false);
        }

        $this->entityManager->flush();
    }

    public function removeRoles(array $roles, bool $andFlush = true): void
    {
        foreach ($roles as $role) {
            $this->removeRole($role, false);
        }

        if ($andFlush) {
            $this->entityManager->flush();
        }
    }
}
