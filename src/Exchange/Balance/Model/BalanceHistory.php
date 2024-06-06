<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Model;

/** @codeCoverageIgnore */
class BalanceHistory
{
    private int $offset;
    private int $limit;
    private array $records;

    public function __construct(int $offset, int $limit, array $records)
    {
        $this->offset = $offset;
        $this->limit = $limit;
        $this->records = $records;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getRecords(): array
    {
        return $this->records;
    }
}
