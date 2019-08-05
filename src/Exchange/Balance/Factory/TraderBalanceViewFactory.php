<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Factory;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Entity\UserCrypto;
use App\Entity\UserToken;
use App\Entity\UserTradebleInterface;
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
        TradebleInterface $tradable,
        int $limit,
        int $extend,
        int $incrementer,
        int $max
    ): array {
        if (0 === count($balances) || $tradable instanceof Token && null === $tradable->getId()) {
            return [];
        }

        $isMax = $max <= $extend || count($balances) < $extend;
        $balances = $this->refactorBalances($balances);

        $usersTradables = count($balances) > 0 ? $this->getUserTradables($tradable, array_keys($balances)) : [];

        if ($isMax || count($usersTradables) >= $limit) {
            return $this->getTraderBalancesView(array_slice($usersTradables, 0, $limit), $balances);
        }

        return $balanceHandler->topHolders($tradable, $limit, $extend + $incrementer, $incrementer, $max);
    }

    /**
     * @param TradebleInterface $tradeble
     * @param int[] $userIds
     * @return array
     */
    private function getUserTradables(TradebleInterface $tradeble, array $userIds): array
    {
        if ($tradeble instanceof Token) {
            return $this->userManager->getUserToken($tradeble, $userIds);
        }

        if ($tradeble instanceof Crypto) {
            return $this->userManager->getUserCrypto($tradeble, $userIds);
        }

         return [];
    }

    /**
     * @param UserTradebleInterface[] $usersTokens
     * @param string[] $balances
     * @return TraderBalanceView[]
     */
    private function getTraderBalancesView(array $usersTokens, array $balances): array
    {
        $traderBalanceViews = array_map(function (UserTradebleInterface $userTradable) use ($balances) {
            $user = $userTradable->getUser();

            return new TraderBalanceView($user, $balances[$user->getId()], $userTradable->getCreated());
        }, $usersTokens);

        usort($traderBalanceViews, function (TraderBalanceView $a, TraderBalanceView $b) {
            return -((float)$a->getBalance() <=> (float)$b->getBalance());
        });

        return $traderBalanceViews;
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
                && 0 < ($userId = (int)$balance[0] - $this->config->getOffset())) {
                $refactoredBalances[$userId] = $balance[1];
            }
        }

        return $refactoredBalances;
    }
}
