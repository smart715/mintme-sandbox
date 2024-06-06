<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Token\Token;
use App\Entity\TopHolder;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Repository\TopHolderRepository;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;

class TopHolderManager implements TopHolderManagerInterface
{
    private TopHolderRepository $repository;
    private EntityManagerInterface $entityManager;
    private BalanceHandlerInterface $balanceHandler;
    private MoneyWrapperInterface $moneyWrapper;
    private TokenManagerInterface $tokenManager;
    private int $limit;

    public function __construct(
        TopHolderRepository $repository,
        EntityManagerInterface $entityManager,
        BalanceHandlerInterface $balanceHandler,
        MoneyWrapperInterface $moneyWrapper,
        TokenManagerInterface $tokenManager,
        int $limit
    ) {
        $this->repository = $repository;
        $this->entityManager = $entityManager;
        $this->balanceHandler = $balanceHandler;
        $this->moneyWrapper = $moneyWrapper;
        $this->tokenManager = $tokenManager;
        $this->limit = $limit;
    }

    public function updateTopHolders(Token $token): void
    {
        $topHoldersFromExchange = $this->balanceHandler->topHolders($token, $this->limit);
        $topHoldersFromDb = $this->repository->findBy(['token' => $token]);
        $topHolderEligibleToBeRemoved = $topHoldersFromDb;

        foreach ($topHoldersFromExchange as $topHolderFromExchange) {
            $user = $topHolderFromExchange->getUser();
            $balance = $this->moneyWrapper->parse(
                $topHolderFromExchange->getBalance(),
                Symbols::TOK
            );
            $rank = $topHolderFromExchange->getRank();

            $holder = $this->findByUser($user, $topHoldersFromDb);

            if ($holder) {
                $topHolderEligibleToBeRemoved = array_filter(
                    $topHolderEligibleToBeRemoved,
                    static fn (TopHolder $topHolder) => $topHolder->getUser()->getId() !== $holder->getUser()->getId()
                );
            }

            if ($holder && $holder->getRank() === $rank && $holder->getAmount()->equals($balance)) {
                continue;
            }

            if (!$holder) {
                $holder = new TopHolder();
                $holder->setUser($user);
                $holder->setToken($token);
            }

            $holder
                ->setRank($rank)
                ->setAmount($balance->getAmount());

            $this->entityManager->persist($holder);
        }

        foreach ($topHolderEligibleToBeRemoved as $topHolder) {
            $this->entityManager->remove($topHolder);
        }

        $this->entityManager->flush();
    }

    /**
     * @param TopHolder[] $topHolders
     */
    private function findByUser(User $user, array $topHolders): ?TopHolder
    {
        foreach ($topHolders as $topHolder) {
            if ($user->getId() === $topHolder->getUser()->getId()) {
                return $topHolder;
            }
        }

        return null;
    }


    public function shouldUpdateTopHolders(User $user, Token $token): bool
    {
        $owner = $token->getOwner();

        if ($owner && $owner->getId() === $user->getId()) {
            return false;
        }

        $balance = $this->balanceHandler->balance($user, $token);
        $lastHolder = $this->repository->findOneBy(['token' => $token, 'rank' => $this->limit]);

        if (!$lastHolder) {
            return true;
        }

        return $balance->getAvailable()->greaterThan($lastHolder->getAmount());
    }

    public function getOwnTopHolders(): array
    {
        $tokens = $this->tokenManager->getOwnTokens();

        return $this->repository->findByTokens($tokens);
    }

    public function getTopHolderByUserAndToken(User $user, Token $token): ?TopHolder
    {
        return $this->repository->findOneBy(['user' => $user, 'token' => $token]);
    }
}
