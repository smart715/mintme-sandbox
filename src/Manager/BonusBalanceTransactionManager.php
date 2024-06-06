<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\BonusBalanceTransaction;
use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Exception\NotFoundTradableException;
use App\Repository\BonusBalanceTransactionRepository;
use App\Repository\CryptoRepository;
use App\Repository\TokenRepository;
use App\Wallet\Model\Status;
use App\Wallet\Model\Transaction;
use App\Wallet\Model\Type;
use Brick\Math\BigInteger;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use Psr\Log\LoggerInterface;

class BonusBalanceTransactionManager implements BonusBalanceTransactionManagerInterface
{

    private BonusBalanceTransactionRepository $repository;

    private EntityManagerInterface $em;

    private UserTokenManagerInterface $userTokenManager;

    private TokenRepository $tokenRepository;

    private CryptoRepository $cryptoRepository;

    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $em,
        UserTokenManagerInterface $userTokenManager,
        BonusBalanceTransactionRepository $repository,
        TokenRepository $tokenRepository,
        CryptoRepository $cryptoRepository,
        LoggerInterface $logger
    ) {
        $this->em = $em;
        $this->repository = $repository;
        $this->userTokenManager = $userTokenManager;
        $this->tokenRepository = $tokenRepository;
        $this->cryptoRepository = $cryptoRepository;
        $this->logger = $logger;
    }

    public function getBalances(User $user): array
    {
        $balances = $this->repository->getBalancesByUser($user);

        return $this->parseBonusTransactions($balances);
    }

    public function updateBalance(User $user, TradableInterface $tradable, Money $amount, string $type, string $bonusType): void
    {
        $transaction = (new BonusBalanceTransaction($tradable))
            ->setUser($user)
            ->setAmount($amount)
            ->setType($type)
            ->setBonusType($bonusType);

        if ($tradable instanceof Token) {
            $this->userTokenManager->updateRelation($user, $tradable, $amount);
        }

        $this->em->persist($transaction);
        $this->em->flush();
    }

    public function getBalance(User $user, TradableInterface $tradable): ?Money
    {
        $balance = $this->repository->getBalance($user, $tradable);

        if (!$balance) {
            return null;
        }

        $symbol = $tradable->getMoneySymbol();

        $deposit = (string)BigInteger::of($balance[Type::DEPOSIT] ?? '0');
        $withdraw = (string)BigInteger::of($balance[Type::WITHDRAW] ?? '0');

        $deposit = new Money($deposit, new Currency($symbol));
        $withdraw = new Money($withdraw, new Currency($symbol));

        return $deposit->subtract($withdraw);
    }

    public function getTransactions(User $user, int $offset, int $limit): array
    {
        return array_map(function (BonusBalanceTransaction $transaction) {
            return new Transaction(
                DateTime::createFromImmutable($transaction->getCreatedAt()),
                '',
                '',
                '',
                $transaction->getAmount(),
                null,
                $transaction->getToken(),
                Status::fromString(Status::PAID),
                Type::fromString($transaction->getType()),
                true,
                $transaction->getCrypto()
            );
        }, $this->repository->getTransactions($user, $offset, $limit));
    }

    /**
     * @return BonusBalanceTransaction[]
     */
    private function parseBonusTransactions(array $transactions): array
    {
        $cryptoIds = array_map(static fn(array $transaction) => (int)$transaction['crypto_id'], $transactions);
        $tokenIds = array_map(static fn(array $transaction) => (int)$transaction['token_id'], $transactions);

        $cryptos = $this->cryptoRepository->findBy(['id' => array_filter($cryptoIds)]);

        $tokens = $this->tokenRepository->findBy(['id' => array_filter($tokenIds)]);

        return array_map(function (array $transaction) use ($cryptos, $tokens) {

            $tradable = $this->getTradableFromTransaction($transaction, $cryptos, $tokens);

            if (null === $tradable) {
                $this->logger->error('Tradable does not exist');

                throw new NotFoundTradableException('Tradable does not exist');
            }

            $depositsSum = (string)BigInteger::of($transaction[Type::DEPOSIT] ?? '0');
            $withdrawalsSum = (string)BigInteger::of($transaction[Type::WITHDRAW] ?? '0');

            $deposit = new Money(
                $depositsSum,
                new Currency($tradable->getMoneySymbol())
            );
            $withdraw = new Money(
                $withdrawalsSum,
                new Currency($tradable->getMoneySymbol())
            );

            $bonusBalanceTransaction = new BonusBalanceTransaction($tradable);
            $bonusBalanceTransaction->setAmount($deposit->subtract($withdraw));

            return $bonusBalanceTransaction;
        }, $transactions);
    }

    /**
     * @param TradableInterface[] $cryptos
     * @param TradableInterface[] $tokens
     */
    private function getTradableFromTransaction(array $transaction, array $cryptos, array $tokens): ?TradableInterface
    {
        $tradables = $transaction['crypto_id']
            ? $cryptos
            : $tokens;

        $id = $transaction['crypto_id'] ?? $transaction['token_id'];

        foreach ($tradables as $tradable) {
            if ((int)$id === $tradable->getId()) {
                return $tradable;
            }
        }

        return null;
    }
}
