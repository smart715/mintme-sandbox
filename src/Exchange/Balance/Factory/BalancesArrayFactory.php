<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Factory;

use App\Exchange\Config\Config;

class BalancesArrayFactory implements BalancesArrayFactoryInterface
{
    /** @var Config */
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /** @inheritDoc */
    public function create(array $balances): array
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
