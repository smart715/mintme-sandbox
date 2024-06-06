<?php declare(strict_types = 1);

namespace App\Wallet\Model;

use App\Entity\Crypto;
use App\Entity\TradableInterface;
use App\Entity\User;
use Money\Money;
use Symfony\Contracts\EventDispatcher\Event;

/** @codeCoverageIgnore */
class LackMainBalanceReport
{
    private User $user;
    private Type $type;
    private Money $amount;
    private TradableInterface $tradable;
    private Money $tradableBalance;
    private Money $tradableNeed;
    private Crypto $cryptoNetwork;
    private Money $networkNeed;
    private Money $networkBalance;
    private ?Crypto $nativeMoneyCrypto;
    private bool $isToken;

    public function __construct(
        User $user,
        Type $type,
        Money $amount,
        TradableInterface $tradable,
        Money $tradableBalance,
        Money $tradableNeed,
        Crypto $cryptoNetwork,
        Money $networkNeed,
        Money $networkBalance,
        ?Crypto $nativeMoneyCrypto,
        bool $isToken
    ) {
        $this->user = $user;
        $this->type = $type;
        $this->amount = $amount;

        $this->tradable = $tradable;
        $this->tradableBalance = $tradableBalance;
        $this->tradableNeed = $tradableNeed;

        $this->cryptoNetwork = $cryptoNetwork;
        $this->networkNeed = $networkNeed;
        $this->networkBalance = $networkBalance;
        $this->isToken = $isToken;
        $this->nativeMoneyCrypto = $nativeMoneyCrypto;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function getTradable(): TradableInterface
    {
        return $this->tradable;
    }

    public function getTradableBalance(): Money
    {
        return $this->tradableBalance;
    }

    public function getTradableAmount(): Money
    {
        return $this->tradableNeed;
    }

    public function getCryptoNetwork(): Crypto
    {
        return $this->cryptoNetwork;
    }

    public function getNetworkAmount(): Money
    {
        return $this->networkNeed;
    }

    public function getNetworkBalance(): Money
    {
        return $this->networkBalance;
    }

    public function getNativeMoneyCrypto(): ?Crypto
    {
        return $this->nativeMoneyCrypto;
    }

    public function isToken(): bool
    {
        return $this->isToken;
    }
}
