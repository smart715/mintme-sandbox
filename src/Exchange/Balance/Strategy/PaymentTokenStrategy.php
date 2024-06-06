<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Strategy;

use App\Entity\Token\Token;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Exception\NotFoundTokenException;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Config\TokenConfig;
use App\Manager\CryptoManagerInterface;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;

class PaymentTokenStrategy implements BalanceStrategyInterface
{
    private BalanceHandlerInterface $balanceHandler;
    private CryptoManagerInterface $cryptoManager;
    private MoneyWrapperInterface $moneyWrapper;
    private TokenConfig $tokenConfig;

    public function __construct(
        BalanceHandlerInterface $balanceHandler,
        CryptoManagerInterface $cryptoManager,
        MoneyWrapperInterface $moneyWrapper,
        TokenConfig $tokenConfig
    ) {
        $this->balanceHandler = $balanceHandler;
        $this->cryptoManager = $cryptoManager;
        $this->moneyWrapper = $moneyWrapper;
        $this->tokenConfig = $tokenConfig;
    }

    /** @param Token $tradable */
    public function deposit(User $user, TradableInterface $tradable, string $amount): void
    {
        if (!$tradable->getFee()) {
            $this->withdrawBaseFee($user, $tradable);
        }

        $this->depositTokens($user, $tradable, $amount);
    }

    private function depositTokens(User $user, Token $token, string $amount): void
    {
        $fullAmount = $this->moneyWrapper->parse($amount, Symbols::TOK);
        $tokenFee = $token->getFee();

        if ($tokenFee) {
            $fullAmount = $fullAmount->add($tokenFee);
        }

        $this->balanceHandler->deposit(
            $user,
            $token,
            $fullAmount
        );
    }

    private function withdrawBaseFee(User $user, Token $token): void
    {
        $crypto = $this->cryptoManager->findBySymbol($token->getCryptoSymbol());

        if (!$crypto) {
            throw new NotFoundTokenException();
        }

        $cryptoSymbol = $crypto->getSymbol();

        $fee = in_array($cryptoSymbol, [Symbols::ETH, Symbols::BNB, Symbols::SOL, Symbols::AVAX])
            ? $this->tokenConfig->getWithdrawFeeByCryptoSymbol($cryptoSymbol)
            : $crypto->getFee();

        if (!$token->getFee()) {
            $this->balanceHandler->deposit(
                $user,
                $crypto,
                $fee
            );
        }
    }
}
