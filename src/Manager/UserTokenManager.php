<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Entity\UserToken;
use App\Repository\UserTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Money\Money;
use Psr\Log\LoggerInterface;

class UserTokenManager implements UserTokenManagerInterface
{
    private UserTokenRepository $repository;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        /** @var UserTokenRepository $repository */
        $repository = $this->entityManager->getRepository(UserToken::class);
        $this->repository = $repository;
        $this->logger = $logger;
    }

    public function updateRelation(User $user, Token $token, Money $balance): void
    {
        if (!$token->getId()) {
            return;
        }

        $userToken = $this->repository->findByUserToken(
            $user->getId(),
            $token->getId()
        );

        $this->logger->info('usertoken', [
            'email' => $user->getEmailAuthRecipient(),
            'isBalanceZero' => $balance->isZero(),
            'exists' => !!$userToken,
            'userToken' => $userToken,
        ]);

        if (!$userToken && !$balance->isZero()) {
            $userToken = (new UserToken())->setToken($token)->setUser($user);
            $this->entityManager->persist($userToken);
            $user->addToken($userToken);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $this->logger->info('usertoken added');
        } elseif ($userToken && $balance->isZero()) {
            $this->entityManager->remove($userToken);
            $user->removeToken($userToken);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $this->logger->info('usertoken removed');
        }

        $userToken2 = $this->repository->findByUserToken(
            $user->getId(),
            $token->getId()
        );

        $this->logger->info('usertoken 2', [
            'email' => $user->getEmailAuthRecipient(),
            'isBalanceZero' => $balance->isZero(),
            'exists' => !!$userToken,
            'userToken' => $userToken,
            'userTokens' => $user->getTokens(),
        ]);
    }
}
