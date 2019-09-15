<?php declare(strict_types = 1);

namespace App\Strategy;

use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Exception\NotFoundTokenException;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Manager\CryptoManagerInterface;
use App\Wallet\Money\MoneyWrapper;
use Money\Currency;
use Money\Money;

class PaymentTokenStrategy implements BalanceStrategyInterface
{
    /** @var BalanceHandlerInterface */
    private $balanceHandler;

    /** @var CryptoManagerInterface */
    private $cryptoManager;

    public function __construct(
        BalanceHandlerInterface $balanceHandler,
        CryptoManagerInterface $cryptoManager
    ) {
        $this->balanceHandler = $balanceHandler;
        $this->cryptoManager = $cryptoManager;
    }

    /** @param Token $tradeble */
    public function deposit(User $user, TradebleInterface $tradeble, string $amount): void
    {
        $this->withdrawWebFee($user);
        $this->depositTokens($user, $tradeble, $amount);
    }

    private function depositTokens(User $user, Token $token, string $amount): void
    {
        $this->balanceHandler->deposit(
            $user,
            $token,
            new Money($amount, new Currency(MoneyWrapper::TOK_SYMBOL))
        );
    }

    private function withdrawWebFee(User $user): void
    {
        $crypto = $this->cryptoManager->findBySymbol(Token::WEB_SYMBOL);

        if (!$crypto) {
            throw new NotFoundTokenException();
        }

        $this->balanceHandler->deposit(
            $user,
            Token::getFromCrypto($crypto),
            $crypto->getFee()
        );
    }
}
