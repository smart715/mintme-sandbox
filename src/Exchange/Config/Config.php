<?php declare(strict_types = 1);

namespace App\Exchange\Config;

/** @codeCoverageIgnore */
class Config
{
    /** @var int $offset */
    private $offset;

    /** @var int */
    private $tokenQuantity;

    public function __construct(int $offset, int $tokenQuantity)
    {
        $this->offset = $offset;
        $this->tokenQuantity = $tokenQuantity;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getTokenQuantity(): int
    {
        return $this->tokenQuantity;
    }
}
