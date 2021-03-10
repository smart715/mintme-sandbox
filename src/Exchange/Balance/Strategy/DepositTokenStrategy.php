<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Strategy;

use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use App\Wallet\WalletInterface;

class DepositTokenStrategy implements BalanceStrategyInterface
{
    /** @var BalanceHandlerInterface */
    private $balanceHandler;

    /** @var WalletInterface */
    private $wallet;

    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    public function __construct(
        BalanceHandlerInterface $balanceHandler,
        WalletInterface $wallet,
        MoneyWrapperInterface $moneyWrapper
    ) {
        $this->balanceHandler = $balanceHandler;
        $this->wallet = $wallet;
        $this->moneyWrapper = $moneyWrapper;
    }

    /** @param Token $tradeble */
    public function deposit(User $user, TradebleInterface $tradeble, string $amount): void
    {
        $this->withdrawBaseFee($user, $tradeble);
        $this->depositTokens($user, $tradeble, $amount);
    }

    private function depositTokens(User $user, Token $token, string $amount): void
    {
        $this->balanceHandler->deposit(
            $user,
            $token,
            $this->moneyWrapper->parse($amount, Symbols::TOK)
        );
    }

    private function withdrawBaseFee(User $user, Token $token): void
    {
        $tokenDepositFee = $this->wallet->getDepositInfo($token)->getFee();

        if ($tokenDepositFee->isNegative() || $tokenDepositFee->isZero()) {
            return;
        }

        $this->balanceHandler->withdraw(
            $user,
            Token::getFromSymbol($token->getCryptoSymbol()),
            $tokenDepositFee
        );
    }
}
