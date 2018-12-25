<?php

namespace App\Withdraw;

use App\Entity\Crypto;
use App\Entity\User;
use App\Withdraw\Communicator\CommunicatorInterface;
use App\Withdraw\Communicator\Model\WithdrawCallbackMessage;
use App\Withdraw\Fetcher\Mapper\MapperInterface;
use App\Withdraw\Payment\Status;
use App\Withdraw\Payment\Transaction;
use Money\Money;

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

    public function withdraw(User $user, Money $balance, string $address, Crypto $crypto): void
    {
        $this->communicator->sendWithdrawRequest($user, $balance, $address, $crypto);
    }

    public function retryWithdraw(WithdrawCallbackMessage $callbackMessage): void
    {
        $this->communicator->sendRetryMessage($callbackMessage);
    }

    /** {@inheritdoc} */
    public function getHistory(User $user, int $offset = 0, int $limit = 50): array
    {
        return $this->mapper->getHistory($user, $offset, $limit);
    }

    /** {@inheritdoc} */
    public function getBalance(Crypto $crypto): Money
    {
        return $this->mapper->getBalance($crypto);
    }
}
