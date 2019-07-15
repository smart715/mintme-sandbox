<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Factory;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandler;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Config\Config;
use App\Manager\UserManager;

class TraderBalanceViewFactory implements TraderBalanceViewFactoryInterface
{
    /**  @var UserManager */
    private $userManager;

    /** @var Config */
    private $config;

    public function __construct(
        UserManager $userManager,
        Config $config
    ) {
        $this->userManager = $userManager;
        $this->config = $config;
    }

    /** @inheritDoc */
    public function create(
        BalanceHandlerInterface $balanceHandler,
        array $tradersBalance,
        Token $token,
        int $limit,
        int $extend,
        int $incrementer
    ): array {
        $traderBalanceViews = [];
        $count = 0;
        $isMax = count($tradersBalance) < $extend;

        foreach ($tradersBalance as $item) {
            if (!isset($item[0]) || !isset($item[1])
                || null === ($user = $this->getUserIfNotIgnored($item[0], $token))) {
                continue;
            }

            $traderBalanceViews[] = new TraderBalanceView($user, $item[1]);
            $count++;

            if ($count >= $limit) {
                return $traderBalanceViews;
            }
        }

        if ($isMax) {
            return $traderBalanceViews;
        }

        return $balanceHandler->topTraders($token, $limit, $extend + $incrementer, $incrementer);
    }

    private function getUserIfNotIgnored(int $userId, Token $token): ?User
    {
        $user = $this->userManager->find($userId - $this->config->getOffset());

        if (null === $user || $this->isOwner($token, $userId) || $this->isAnonymous($user)) {
            return null;
        }

        return $user;
    }

    private function isAnonymous(User $user): bool
    {
        return null === $user->getProfile() || $user->getProfile()->isAnonymous();
    }

    private function isOwner(Token $token, int $userId): bool
    {
        return ($userId - $this->config->getOffset()) === $token->getProfile()->getUser()->getId();
    }
}
