<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Factory;

use App\Exchange\Balance\BalanceHandlerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\TopHolderManagerInterface;

class TokensUserOwnsViewFactory implements TokensUserOwnsViewFactoryInterface
{
    private TokenManagerInterface $tokenManager;
    private BalanceHandlerInterface $balanceHandler;
    private TopHolderManagerInterface $topHolderManager;

    public function __construct(
        TokenManagerInterface $tokenManager,
        BalanceHandlerInterface $balanceHandler,
        TopHolderManagerInterface $topHolderManager
    ) {
        $this->tokenManager = $tokenManager;
        $this->balanceHandler = $balanceHandler;
        $this->topHolderManager = $topHolderManager;
    }

    /** {@inheritdoc} */
    public function create(array $userTokens, bool $isHideZeroBalanceTokens = false): array
    {
        $refactoredTokens = [];

        foreach ($userTokens as $userToken) {
            $token = $userToken->getToken();
            $user = $userToken->getUser();
            $name = $token->getName();
            $topHolder = $this->topHolderManager->getTopHolderByUserAndToken($user, $token);

            $balance = $topHolder
                ? $topHolder->getAmount()
                : $this->tokenManager->getRealBalance(
                    $token,
                    $this->balanceHandler->balance($user, $token),
                    $user
                )->getAvailable();

            if ($isHideZeroBalanceTokens && '0' === $balance->getAmount()) {
                continue;
            }

            $refactoredTokens[$name] = new TokensUserOwnsView(
                $name,
                $balance,
                $token->getDecimals(),
                $token->getImage(),
                $token->getCryptoSymbol(),
                $token->getDeployed() && $token->isCreatedOnMintmeSite(),
                $topHolder ? $topHolder->getRank() : null,
            );
        }

        if (count($refactoredTokens) > 1) {
            usort($refactoredTokens, static function ($a, $b) {
                return $a->getAvailable()->lessThan($b->getAvailable())
                    ? 1
                    : -1;
            });
        }

        return $refactoredTokens;
    }
}
