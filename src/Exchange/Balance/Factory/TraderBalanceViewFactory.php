<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Factory;

use App\Entity\Token\Token;
use App\Entity\UserToken;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Config\Config;
use App\Manager\UserManagerInterface;

class TraderBalanceViewFactory implements TraderBalanceViewFactoryInterface
{
    /**  @var UserManagerInterface */
    private $userManager;

    /** @var Config */
    private $config;

    public function __construct(
        UserManagerInterface $userManager,
        Config $config
    ) {
        $this->userManager = $userManager;
        $this->config = $config;
    }

    /** @inheritDoc */
    public function create(
        BalanceHandlerInterface $balanceHandler,
        array $balances,
        Token $token,
        int $limit,
        int $extend,
        int $incrementer
    ): array {
        if (null === $token->getId()) {
            return [];
        }

        $isMax = count($balances) < $extend;
        $balances = $this->refactorBalances($balances);

        /** @var UserToken[] $tokenUsers */
        $usersTokens = $this->userManager->getUserToken($token->getId(), array_keys($balances));

        if ($isMax || count($usersTokens) >= $limit) {
            return $this->getTraderBalancesView(array_slice($usersTokens, 0, $limit), $balances);
        }

        return $balanceHandler->topTraders($token, $limit, $extend + $incrementer, $incrementer);
    }

    /**
     * @param UserToken[] $usersTokens
     * @param string[] $balances
     * @return TraderBalanceView[]
     */
    private function getTraderBalancesView(array $usersTokens, array $balances): array
    {
        return array_map(function (UserToken $userToken) use ($balances) {
            $user = $userToken->getUser();

            return new TraderBalanceView($user, $balances[$user->getId()], $userToken->getCreated());
        }, $usersTokens);
    }

    /**
     * @param string[] $balances
     * @return string[]
     */
    private function refactorBalances(array $balances): array
    {
        $refactoredBalances = [];

        foreach ($balances as $balance) {
            if (isset($balance[0]) && isset($balance[1])
                && 0 < ($userId =(int)$balance[0] - $this->config->getOffset())) {
                $refactoredBalances[$userId] = $balance[1];
            }
        }

        return $refactoredBalances;
    }
}
