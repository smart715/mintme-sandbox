<?php declare(strict_types = 1);

namespace App\Manager;

use App\Config\LimitHistoryConfig;
use App\Entity\Crypto;
use App\Entity\InternalTransaction\CryptoInternalTransaction;
use App\Entity\InternalTransaction\InternalTransaction;
use App\Entity\InternalTransaction\TokenInternalTransaction;
use App\Entity\Token\Token;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Manager\Model\InternalTransferModel;
use App\Repository\InternalTransactionRepository;
use App\Utils\Symbols;
use App\Wallet\Model\Address;
use App\Wallet\Model\Amount;
use App\Wallet\Model\Exception\StatusException;
use App\Wallet\Model\Exception\TypeException;
use App\Wallet\Model\Status;
use App\Wallet\Model\Transaction;
use App\Wallet\Model\Type;
use Money\Currency;
use Money\Money;

class InternalTransactionManager implements InternalTransactionManagerInterface
{
    private InternalTransactionRepository $internalTransactionRepository;
    private LimitHistoryConfig $limitHistoryConfig;

    public function __construct(
        InternalTransactionRepository $internalTransactionRepository,
        LimitHistoryConfig $limitHistoryConfig
    ) {
        $this->internalTransactionRepository = $internalTransactionRepository;
        $this->limitHistoryConfig = $limitHistoryConfig;
    }

    public function transferFunds(
        User $user,
        User $recipient,
        TradableInterface $tradable,
        Crypto $cryptoNetwork,
        Amount $amount,
        Address $address,
        Money $fee
    ): InternalTransferModel {
        $internalDeposit = $this->makeWithdraw($user, $tradable, $cryptoNetwork, $amount, $address, $fee);
        $internalWithdrawal = $this->makeDeposit($recipient, $tradable, $cryptoNetwork, $amount, $address);

        return new InternalTransferModel($internalWithdrawal, $internalDeposit);
    }

    public function getLatest(
        User $user,
        int $offset,
        int $limit
    ): array {
        return $this->mapTransactions($this->internalTransactionRepository->getLatest(
            $user,
            $offset,
            $limit,
            $this->limitHistoryConfig->getFromDate()
        ));
    }

    public function getInternalTransactionsProfits(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        return $this->internalTransactionRepository->getInternalTransactionsPerCrypto($startDate, $endDate);
    }

    /**
     * @param InternalTransaction[] $transactions
     * @return array
     * @throws StatusException
     * @throws TypeException
     */
    private function mapTransactions(array $transactions): array
    {
        return array_map(function (InternalTransaction $transaction) {
            return new Transaction(
                $transaction->getDate(),
                null,
                null,
                $transaction->getAddress()->getAddress(),
                $transaction->getAmount()->getAmount(),
                $transaction->getFee(),
                $transaction->getTradable(),
                Status::fromString(Status::PAID),
                Type::fromString($transaction->getType()),
                false,
                $transaction->getCryptoNetwork()
            );
        }, $transactions);
    }

    private function makeDeposit(
        User $user,
        TradableInterface $tradable,
        Crypto $cryptoNetwork,
        Amount $amount,
        Address $address
    ): InternalTransaction {
        if (!$tradable instanceof Crypto && !$tradable instanceof Token) {
            throw new \InvalidArgumentException('Invalid tradable');
        }

        $fee = new Money(0, new Currency($tradable instanceof Token ? Symbols::TOK: $tradable->getSymbol()));

        return $tradable instanceof Token
            ? new TokenInternalTransaction($user, $tradable, $cryptoNetwork, $amount, $address, $fee, Type::DEPOSIT)
            : new CryptoInternalTransaction($user, $tradable, $cryptoNetwork, $amount, $address, $fee, Type::DEPOSIT);
    }

    private function makeWithdraw(
        User $user,
        TradableInterface $tradable,
        Crypto $cryptoNetwork,
        Amount $amount,
        Address $address,
        Money $fee
    ): InternalTransaction {
        if (!$tradable instanceof Crypto && !$tradable instanceof Token) {
            throw new \InvalidArgumentException('Invalid tradable');
        }

        return $tradable instanceof Token
            ? new TokenInternalTransaction($user, $tradable, $cryptoNetwork, $amount, $address, $fee, Type::WITHDRAW)
            : new CryptoInternalTransaction($user, $tradable, $cryptoNetwork, $amount, $address, $fee, Type::WITHDRAW);
    }
}
