<?php

namespace App\Withdraw;

use App\Entity\Crypto;
use App\Entity\User;
use App\Withdraw\Communicator\CommunicatorInterface;
use App\Withdraw\Fetcher\Mapper\MapperInterface;

class CryptoWithdrawGateway implements WithdrawGatewayInterface
{
    /** @var CommunicatorInterface */
    private $communicator;

    /** @var MapperInterface */
    private $mapper;

    public function __construct(CommunicatorInterface $communicator, MapperInterface $mapper)
    {
        $this->communicator = $communicator;
        $this->mapper = $mapper;
    }

    public function withdraw(User $user, string $balance, string $address, Crypto $crypto): void
    {
        $this->communicator->sendWithdrawRequest($user, $balance, $address, $crypto);
    }

    /** {@inheritdoc} */
    public function getHistory(User $user): array
    {
        return $this->mapper->getHistory($user);
    }

    /** {@inheritdoc} */
    public function getBalance(Crypto $crypto): array
    {
        return $this->mapper->getBalance($crypto);
    }
}
