<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Model;

class BalanceResultContainer implements \IteratorAggregate
{
    /** @var mixed[] */
    private $balances;

    private function __construct(array $balances)
    {
        $this->balances = $balances;
    }

    public function getAll(): array
    {
        return $this->balances;
    }

    public function get(string $symbol): BalanceResult
    {
        return $this->getAll()[$symbol] ?? BalanceResult::fail($symbol);
    }

    public static function success(array $balances): self
    {
        return new self($balances);
    }

    public static function fail(): self
    {
        return new self([]);
    }

    /** {@inheritdoc} */
    public function getIterator()
    {
        return new \ArrayIterator($this->getAll());
    }
}
