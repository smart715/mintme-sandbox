<?php declare(strict_types = 1);

namespace App\Utils\Policy;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Manager\TokenManagerInterface;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class NotificationPolicy implements NotificationPolicyInterface
{
    private TokenManagerInterface $tokenManager;
    private BalanceHandlerInterface $balanceHandler;
    private MoneyWrapperInterface  $moneyWrapper;
    private ContainerInterface $container;

    public function __construct(
        TokenManagerInterface $tokenManager,
        ContainerInterface $container,
        BalanceHandlerInterface $balanceHandler,
        MoneyWrapperInterface $moneyWrapper
    ) {
        $this->tokenManager = $tokenManager;
        $this->balanceHandler = $balanceHandler;
        $this->container = $container;
        $this->moneyWrapper = $moneyWrapper;
    }

    public function canReceiveNotification(User $user, Token $token): bool
    {
        $minTokensAmount = $this->moneyWrapper->parse(
            (string)$this->container->getParameter('min_wallet_tokens_amount'),
            Symbols::TOK
        );

        $available = $this->tokenManager->getRealBalance(
            $token,
            $this->balanceHandler->balance($user, $token),
            $user
        )->getAvailable();

        return $available->greaterThanOrEqual($minTokensAmount);
    }
}
