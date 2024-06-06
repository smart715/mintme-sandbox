<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Strategy;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Wallet\Money\MoneyWrapperInterface;

class DepositCryptoStrategy implements BalanceStrategyInterface
{
    /** @var BalanceHandlerInterface */
    private $balanceHandler;

    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    public function __construct(BalanceHandlerInterface $balanceHandler, MoneyWrapperInterface $moneyWrapper)
    {
        $this->balanceHandler = $balanceHandler;
        $this->moneyWrapper = $moneyWrapper;
    }

    /** @param Crypto $tradable */
    public function deposit(User $user, TradableInterface $tradable, string $amount): void
    {
        $this->balanceHandler->deposit(
            $user,
            $tradable,
            $this->moneyWrapper->parse($amount, $tradable->getSymbol())
        );
    }
}
