<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Entity\UserToken;
use App\Repository\UserTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Money\Money;

class UserTokenManager implements UserTokenManagerInterface
{
    private UserTokenRepository $repository;
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager, UserTokenRepository $repository)
    {
        $this->entityManager = $entityManager;
        $this->repository = $repository;
    }

    /** @inheritDoc */
    public function findByUser(User $user): array
    {
        return $this->repository->findBy(
            [
                'user' => $user,
                'isRemoved' => false,
            ]
        );
    }

    public function findByUserToken(User $user, Token $token): ?UserToken
    {
        return $this->repository->findByUserToken(
            $user->getId(),
            $token->getId()
        );
    }

    public function updateRelation(User $user, Token $token, Money $balance, bool $isReferral = false): void
    {
        $userToken = $this->findByUserToken($user, $token);

        if (!$userToken) {
            $userToken = (new UserToken())
                ->setToken($token)
                ->setUser($user);

            $user->addToken($userToken);
            $this->entityManager->persist($user);
        }

        $userToken
            ->setIsHolder(!$balance->isZero())
            ->setIsReferral($isReferral);

        if (!$token->isBlocked()) {
            $userToken->setIsRemoved(false);
        }

        $this->entityManager->persist($userToken);
        $this->entityManager->flush();
    }

    public function getUserOwnsCount(int $userId): int
    {
        return $this->repository->getUserOwnsCount($userId);
    }

    /** @inheritDoc */
    public function getHoldersWithDiscord(Token $token): array
    {
        return $this->repository->findWithDiscordByToken($token);
    }
}
