<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Model;

class SummaryResult
{
    /** @var string */
    private $name;

    /** @var int */
    private $total;

    /** @var int */
    private $available;

    /** @var int */
    private $availableCount;

    /** @var int */
    private $freeze;

    /** @var int */
    private $freezeCount;

    private function __construct(
        string $name,
        int $total,
        int $available,
        int $availableCount,
        int $freeze,
        int $freezeCount
    ) {
        $this->name = $name;
        $this->total = $total;
        $this->available = $available;
        $this->availableCount = $availableCount;
        $this->freeze = $freeze;
        $this->freezeCount = $freezeCount;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getAvailable(): int
    {
        return $this->available;
    }

    public function getAvailableCount(): int
    {
        return $this->availableCount;
    }

    public function getFreeze(): int
    {
        return $this->freeze;
    }

    public function getFreezeCount(): int
    {
        return $this->freezeCount;
    }

    public function isFailed(): bool
    {
        return empty($this->name);
    }

    public static function success(
        string $name,
        int $total,
        int $available,
        int $availableCount,
        int $freeze,
        int $freezeCount
    ): self {
        return new self($name, $total, $available, $availableCount, $freeze, $freezeCount);
    }

    public static function fail(): self
    {
        return new self('', 0, 0, 0, 0, 0);
    }
}
