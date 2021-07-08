<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
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

    public function findByUserToken(User $user, TradebleInterface $token): ?UserToken
    {
        return $this->repository->findByUserToken(
            $user->getId(),
            $token->getId()
        );
    }

    public function updateRelation(User $user, TradebleInterface $token, Money $balance): void
    {
        if ($token instanceof Crypto) {
            return;
        }

        $userToken = $this->findByUserToken($user, $token);
        $isCreator = $user->getId() === $token->getProfile()->getUser()->getId();

        if (!$userToken && ($isCreator || !$balance->isZero())) {
            $userToken = (new UserToken())
                ->setToken($token)
                ->setUser($user)
                ->setIsHolder(!$balance->isZero());
            $this->entityManager->persist($userToken);
            $user->addToken($userToken);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        } elseif ($userToken) {
            $userToken = $userToken->setIsHolder(!$balance->isZero());
            $this->entityManager->persist($userToken);
            $this->entityManager->flush();
        }
    }
}
