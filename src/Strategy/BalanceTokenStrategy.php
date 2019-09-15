<?php declare(strict_types = 1);

namespace App\Strategy;

use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Entity\UserToken;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Wallet\Money\MoneyWrapper;
use App\Wallet\WalletInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;

class BalanceTokenStrategy implements BalanceStrategyInterface
{
    /** @var BalanceHandlerInterface */
    private $balanceHandler;

    /** @var WalletInterface */
    private $wallet;

    /** @var EntityManagerInterface */
    private $em;

    public function __construct(
        BalanceHandlerInterface $balanceHandler,
        WalletInterface $wallet,
        EntityManagerInterface $em
    ) {
        $this->balanceHandler = $balanceHandler;
        $this->wallet = $wallet;
        $this->em = $em;
    }

    /** @param Token $tradeble */
    public function deposit(User $user, TradebleInterface $tradeble, string $amount): void
    {
        $this->withdrawWebFee($user, $tradeble);
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

    private function withdrawWebFee(User $user, Token $token): void
    {
        $this->balanceHandler->withdraw(
            $user,
            Token::getFromSymbol(Token::WEB_SYMBOL),
            $this->wallet->getFee(
                Token::getFromSymbol(Token::WEB_SYMBOL)
            )
        );

        if (!in_array($user, $token->getUsers(), true)) {
            $userToken = (new UserToken())->setToken($token)->setUser($user);
            $this->em->persist($userToken);
            $user->addToken($userToken);
            $this->em->persist($user);
            $this->em->flush();
        }
    }
}
