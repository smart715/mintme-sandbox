<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Factory;

use App\Entity\UserToken;
use App\Entity\UserTradebleInterface;

class TraderBalanceViewFactory implements TraderBalanceViewFactoryInterface
{
    /** @inheritDoc */
    public function create(array $usersTokens, array $balances): array
    {
        $traderBalanceViews = [];

        /** @var UserToken $usersToken */
        foreach ($usersTokens as $usersToken) {
            $user = $usersToken->getUser();

            if (isset($balances[$user->getId()])) {
                $traderBalanceViews[] = new TraderBalanceView(
                    $user,
                    $balances[$user->getId()],
                    $usersToken->getCreated()
                );
            }
        }

        usort($traderBalanceViews, function (TraderBalanceView $a, TraderBalanceView $b) {
            return -((float)$a->getBalance() <=> (float)$b->getBalance());
        });

        return $traderBalanceViews;
    }
}
