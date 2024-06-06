<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Model;

use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Currency;
use Money\Money;

/** @codeCoverageIgnore */
class BalanceResultFactory
{
    /** @var mixed[] */
    private $rows;

    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    /** @param mixed[] $rows */
    public function __construct(array $rows, MoneyWrapperInterface $moneyWrapper)
    {
        $this->rows = $rows;
        $this->moneyWrapper = $moneyWrapper;
    }

    public function create(): BalanceResultContainer
    {
        $res = [];

        foreach ($this->rows as $symbol => $balance) {
            $res[$symbol] = BalanceResult::success(
                $this->getMoney($balance['available'], $symbol),
                $this->getMoney($balance['freeze'], $symbol),
                $this->getMoney($balance['referral'], $symbol)
            );
        }

        return BalanceResultContainer::success($res);
    }

    private function getMoney(string $value, string $symbol): Money
    {
        if (!$this->moneyWrapper->getRepository()->contains(new Currency($symbol))) {
            $symbol = Symbols::TOK;
        }

        return $this->moneyWrapper->parse($value, $symbol);
    }
}
