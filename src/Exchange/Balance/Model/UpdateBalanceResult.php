<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Model;

/** @codeCoverageIgnore */
class UpdateBalanceResult
{
    private const SUCCESS = 'success';

    private string $change;

    private string $status;

    public function __construct(string $change, string $status)
    {
        $this->change = $change;
        $this->status = $status;
    }

    public function getChange(): string
    {
        return $this->change;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isSuccess(): bool
    {
        return self::SUCCESS === strtolower($this->getStatus());
    }
}
