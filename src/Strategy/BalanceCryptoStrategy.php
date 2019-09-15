<?php declare(strict_types = 1);

namespace App\Strategy;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Wallet\Money\MoneyWrapperInterface;

class BalanceCryptoStrategy implements BalanceStrategyInterface
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

    /** @param Crypto $tradeble */
    public function deposit(User $user, TradebleInterface $tradeble, string $amount): void
    {
        $this->balanceHandler->deposit(
            $user,
            Token::getFromCrypto($tradeble),
            $this->moneyWrapper->parse($amount, $tradeble->getSymbol())
        );
    }
}
