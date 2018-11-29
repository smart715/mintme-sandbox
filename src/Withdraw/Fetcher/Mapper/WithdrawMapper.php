<?php

namespace App\Withdraw\Fetcher\Mapper;

use App\Entity\Crypto;
use App\Entity\User;
use App\Manager\CryptoManagerInterface;
use App\Withdraw\Fetcher\Storage\StorageAdapterInterface;
use App\Withdraw\Payment\Status;
use App\Withdraw\Payment\Transaction;
use Money\Currency;
use Money\Money;

class WithdrawMapper implements MapperInterface
{
    /** @var StorageAdapterInterface */
    private $storage;

    /** @var CryptoManagerInterface */
    private $cryptoManager;

    public function __construct(
        StorageAdapterInterface $storage,
        CryptoManagerInterface $cryptoManager
    ) {
        $this->storage = $storage;
        $this->cryptoManager = $cryptoManager;
    }

    /** {@inheritdoc} */
    public function getHistory(User $user, int $offset = 0, int $limit = 50): array
    {
        return array_map(function (array $transaction) {
            return new Transaction(
                (new \DateTime())->setTimestamp($transaction['createdDate']),
                $transaction['amount'],
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
        return new Money(
            $this->storage->requestBalance($crypto->getSymbol()),
            new Currency($crypto->getSymbol())
        );
    }
}
