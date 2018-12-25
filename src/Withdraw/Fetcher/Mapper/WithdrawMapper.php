<?php

namespace App\Withdraw\Fetcher\Mapper;

use App\Entity\Crypto;
use App\Entity\User;
use App\Manager\CryptoManagerInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use App\Withdraw\Fetcher\Storage\StorageAdapterInterface;
use App\Withdraw\Payment\Status;
use App\Withdraw\Payment\Transaction;
use Money\Money;

class WithdrawMapper implements MapperInterface
{
    /** @var StorageAdapterInterface */
    private $storage;

    /** @var CryptoManagerInterface */
    private $cryptoManager;

    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    public function __construct(
        StorageAdapterInterface $storage,
        CryptoManagerInterface $cryptoManager,
        MoneyWrapperInterface $moneyWrapper
    ) {
        $this->storage = $storage;
        $this->cryptoManager = $cryptoManager;
        $this->moneyWrapper = $moneyWrapper;
    }

    /** {@inheritdoc} */
    public function getHistory(User $user, int $offset = 0, int $limit = 50): array
    {
        return array_map(function (array $transaction) {
            return new Transaction(
                (new \DateTime())->setTimestamp($transaction['createdDate']),
                $this->moneyWrapper->parse($transaction['amount'], $transaction['crypto']),
                $transaction['fee'],
                Status::fromString(
                    $transaction['status']
                ),
                $transaction['transactionHash'],
                $transaction['walletAddress'],
                $this->cryptoManager->findBySymbol(
                    strtoupper($transaction['crypto'])
                )
            );
        }, $this->storage->requestHistory($user->getId(), $offset, $limit));
    }

    public function getBalance(Crypto $crypto): Money
    {
        return $this->moneyWrapper->parse(
            $this->storage->requestBalance($crypto->getSymbol()),
            $crypto->getSymbol()
        );
    }
}
