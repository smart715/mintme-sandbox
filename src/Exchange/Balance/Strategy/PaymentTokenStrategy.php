<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Strategy;

use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Exception\NotFoundTokenException;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Manager\CryptoManagerInterface;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PaymentTokenStrategy implements BalanceStrategyInterface
{
    private BalanceHandlerInterface $balanceHandler;

    private CryptoManagerInterface $cryptoManager;

    private MoneyWrapperInterface $moneyWrapper;

    private ParameterBagInterface $parameterBag;

    public function __construct(
        BalanceHandlerInterface $balanceHandler,
        CryptoManagerInterface $cryptoManager,
        MoneyWrapperInterface $moneyWrapper,
        ParameterBagInterface $parameterBag
    ) {
        $this->balanceHandler = $balanceHandler;
        $this->cryptoManager = $cryptoManager;
        $this->moneyWrapper = $moneyWrapper;
        $this->parameterBag = $parameterBag;
    }

    /** @param Token $tradeble */
    public function deposit(User $user, TradebleInterface $tradeble, string $amount): void
    {
        if (!$tradeble->getFee()) {
            $this->withdrawBaseFee($user, $tradeble);
        }

        $this->depositTokens($user, $tradeble, $amount);
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

        $fee = Symbols::ETH === $crypto->getSymbol()
            ? $this->moneyWrapper->parse(
                (string)$this->parameterBag->get('token_withdraw_fee'),
                Symbols::ETH
            ) : $crypto->getFee();

        if (!$token->getFee()) {
            $this->balanceHandler->deposit(
                $user,
                Token::getFromCrypto($crypto),
                $fee
            );
        }
    }
}
