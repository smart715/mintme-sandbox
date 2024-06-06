<?php declare(strict_types = 1);

namespace App\Wallet\Withdraw;

use App\Entity\Crypto;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Wallet\Withdraw\Communicator\CommunicatorInterface;
use App\Wallet\Withdraw\Communicator\Model\WithdrawCallbackMessage;
use App\Wallet\Withdraw\Fetcher\Mapper\MapperInterface;
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

    public function withdraw(
        User $user,
        Money $balance,
        string $address,
        Crypto $crypto,
        ?Money $fee = null
    ): void {
        $this->communicator->sendWithdrawRequest($user, $balance, $address, $crypto, $fee);
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
    public function getBalance(TradableInterface $tradable, Crypto $cryptoNetwork): Money
    {
        return $this->mapper->getBalance($tradable, $cryptoNetwork);
    }

    public function isContractAddress(string $address, string $crypto): bool
    {
        return $this->mapper->isContractAddress($address, $crypto);
    }

    public function getUserId(string $address, string $cryptoNetwork): ?int
    {
        return $this->mapper->getUserId($address, $cryptoNetwork);
    }

    public function getCryptoIncome(string $crypto, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return $this->mapper->getCryptoIncome($crypto, $from, $to);
    }
}
