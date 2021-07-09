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

    public function removeRole(DiscordRole $role): void
    {
        $this->entityManager->remove($role);
        $this->entityManager->flush();
    }

    public function removeRoles(Token $token): void
    {
        foreach ($token->getDiscordRoles() as $role) {
            $this->entityManager->remove($role);
        }

        $this->entityManager->flush();
    }
}
