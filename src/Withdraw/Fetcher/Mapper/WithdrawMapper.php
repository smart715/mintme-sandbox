<?php

namespace App\Withdraw\Fetcher\Mapper;

use App\Entity\Crypto;
use App\Entity\User;
use App\Withdraw\Fetcher\Storage\StorageAdapterInterface;
use App\Withdraw\Payment\Status;
use App\Withdraw\Payment\Transaction;

class WithdrawMapper implements MapperInterface
{
    /** @var StorageAdapterInterface */
    private $storage;

    public function __construct(StorageAdapterInterface $storage)
    {
        $this->storage = $storage;
    }

    /** {@inheritdoc} */
    public function getHistory(User $user): array
    {
        return array_map(function (array $transaction) {
            return new Transaction(
                $transaction['tx_hash'],
                $transaction['tx_key'],
                Status::fromString(
                    $transaction['status']
                )
            );
        }, $this->storage->requestHistory($user->getId()));
    }

    /** {@inheritdoc} */
    public function getBalance(Crypto $crypto): array
    {
        return $this->storage->requestBalance($crypto->getSymbol());
    }
}
