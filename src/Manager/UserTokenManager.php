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

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        /** @var UserTokenRepository $repository */
        $repository = $this->entityManager->getRepository(UserToken::class);
        $this->repository = $repository;
    }

    public function findByUserToken(User $user, Token $token): ?UserToken
    {
        return $this->repository->findByUserToken(
            $user->getId(),
            $token->getId()
        );
    }

    public function updateRelation(User $user, Token $token, Money $balance): void
    {
        if (!$token->getId()) {
            return;
        }

        $userToken = $this->findByUserToken($user, $token);

        if (!$userToken && !$balance->isZero()) {
            $userToken = (new UserToken())->setToken($token)->setUser($user);
            $this->entityManager->persist($userToken);
            $user->addToken($userToken);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        } elseif ($userToken && $balance->isZero()) {
            $this->entityManager->remove($userToken);
            $user->removeToken($userToken);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
    }
}
