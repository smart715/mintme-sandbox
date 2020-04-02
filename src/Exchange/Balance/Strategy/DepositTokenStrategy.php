<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Strategy;

use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Entity\UserToken;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Wallet\Money\MoneyWrapper;
use App\Wallet\Money\MoneyWrapperInterface;
use App\Wallet\WalletInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;

class DepositTokenStrategy implements BalanceStrategyInterface
{
    /** @var BalanceHandlerInterface */
    private $balanceHandler;

    /** @var WalletInterface */
    private $wallet;

    /** @var EntityManagerInterface */
    private $em;

    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    public function __construct(
        BalanceHandlerInterface $balanceHandler,
        WalletInterface $wallet,
        EntityManagerInterface $em,
        MoneyWrapperInterface $moneyWrapper
    ) {
        $this->balanceHandler = $balanceHandler;
        $this->wallet = $wallet;
        $this->em = $em;
        $this->moneyWrapper = $moneyWrapper;
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
            $this->moneyWrapper->parse($amount, MoneyWrapper::TOK_SYMBOL)
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
