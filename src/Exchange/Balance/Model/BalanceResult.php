<?php

namespace App\Exchange\Balance\Model;

use Symfony\Component\Serializer\Annotation\Groups;

class BalanceResult
{
    /**
     * @var float
     * @Groups({"API"})
     */
    private $available;

    /** @var float */
    private $freeze;

    /** @var bool */
    private $isFailed = false;

    private function __construct(float $abailable, float $freeze)
    {
        $this->available = $abailable;
        $this->freeze = $freeze;
    }

    public function getAvailable(): float
    {
        return $this->available;
    }

    public function getFreeze(): float
    {
        return $this->freeze;
    }

    public function isFailed(): bool
    {
        return $this->isFailed;
    }

    public static function success(float $available, float $freeze): self
    {
        return new self($available, $freeze);
    }

    public static function fail(): self
    {
        $result = new self(0, 0);

        $result->isFailed = true;

        return $result;
    }
}
