<?php declare(strict_types = 1);

namespace App\Exchange\Config;

/** @codeCoverageIgnore */
class Config
{
    /** @var int $offset */
    private $offset;

    /** @var bool */
    private $marketConsumerEnabled;

    public function __construct(int $offset, bool $marketConsumerEnabled)
    {
        $this->offset = $offset;
        $this->marketConsumerEnabled = $marketConsumerEnabled;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function isMarketConsumerEnabled(): bool
    {
        return $this->marketConsumerEnabled;
    }
}
