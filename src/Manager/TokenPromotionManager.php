<?php declare(strict_types = 1);

namespace App\Manager;

use App\Communications\Exception\FetchException;
use App\Communications\TokenPromotionCostFetcherInterface;
use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\Token\TokenPromotion;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\Exception\BalanceException;
use App\Repository\TokenPromotionRepository;
use Doctrine\ORM\EntityManagerInterface;

class TokenPromotionManager implements TokenPromotionManagerInterface
{
    public const BUY_PROMOTION_ID = 'buy_promotion';

    public TokenPromotionRepository $repository;

    private EntityManagerInterface $entityManager;
    private TokenPromotionCostFetcherInterface $tokenPromotionCostFetcher;
    private BalanceHandlerInterface $balanceHandler;

    public function __construct(
        TokenPromotionRepository $repository,
        EntityManagerInterface $entityManager,
        TokenPromotionCostFetcherInterface $tokenPromotionCostFetcher,
        BalanceHandlerInterface $balanceHandler
    ) {
        $this->repository = $repository;
        $this->entityManager = $entityManager;
        $this->tokenPromotionCostFetcher = $tokenPromotionCostFetcher;
        $this->balanceHandler = $balanceHandler;
    }

    public function findActivePromotionsByToken(Token $token): array
    {
        return $this->repository->findActivePromotionsByToken($token);
    }

    public function findActivePromotions(): array
    {
        return $this->repository->findActivePromotions();
    }

    /**
     * @throws \Throwable|BalanceException|FetchException
     */
    public function buyPromotion(Token $token, array $tariff, Crypto $payCrypto): TokenPromotion
    {
        $endDate = (new \DateTimeImmutable())->modify('+' . $tariff['duration']);
        $user = $token->getOwner();
        $balance = $this->balanceHandler->balance($user, $payCrypto)->getAvailable();
        $tariffCost = $this->tokenPromotionCostFetcher->getCost($tariff['duration'])[$payCrypto->getSymbol()];

        $tokenPromotion = new TokenPromotion($user, $token, $endDate, $tariffCost, $payCrypto->getSymbol());

        if ($balance->lessThan($tariffCost)) {
            throw new BalanceException('Not enough balance to buy promotion');
        }

        try {
            $this->balanceHandler->beginTransaction();
            $this->balanceHandler->update(
                $user,
                $payCrypto,
                $tariffCost->negative(),
                self::BUY_PROMOTION_ID
            );

            $this->entityManager->persist($tokenPromotion);
            $this->entityManager->flush();
        } catch (\Throwable $e) {
            $this->balanceHandler->rollback();

            throw $e;
        }

        return $tokenPromotion;
    }
}
