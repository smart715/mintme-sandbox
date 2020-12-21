<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Strategy;

use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Exception\NotFoundTokenException;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Manager\CryptoManagerInterface;
use App\Wallet\Money\MoneyWrapper;
use App\Wallet\Money\MoneyWrapperInterface;

class PaymentTokenStrategy implements BalanceStrategyInterface
{
    /** @var BalanceHandlerInterface */
    private $balanceHandler;

    /** @var CryptoManagerInterface */
    private $cryptoManager;

    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    public function __construct(
        BalanceHandlerInterface $balanceHandler,
        CryptoManagerInterface $cryptoManager,
        MoneyWrapperInterface $moneyWrapper
    ) {
        $this->balanceHandler = $balanceHandler;
        $this->cryptoManager = $cryptoManager;
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
            $this->moneyWrapper->parse($amount, MoneyWrapper::TOK_SYMBOL)
        );
    }

    private function withdrawBaseFee(User $user, Token $token): void
    {
        $crypto = $this->cryptoManager->findBySymbol($token->getCryptoSymbol());

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
