<?php declare(strict_types = 1);

namespace App\Exchange\Config;

/** @codeCoverageIgnore */
class Config
{
    /** @var int $offset */
    private $offset;

    /** @var float $tokenWithdrawFee */
    private $tokenWithdrawFee;

    public function __construct(int $offset, float $tokenWithdrawFee)
    {
        $this->offset = $offset;
        $this->tokenWithdrawFee = $tokenWithdrawFee;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getTokenWithdrawFee(): float
    {
        return $this->tokenWithdrawFee;
    }
}
