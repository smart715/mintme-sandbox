<?php declare(strict_types = 1);

namespace App\SmartContract\Config;

/** @codeCoverageIgnore */
class Config
{
    /** @var string */
    private $mintmeAddress;

    /** @var int */
    private $tokenPrecision;

    /** @var string */
    private $tokenQuantity;

    public function __construct(string $mintmeAddress, int $tokenPrecision, string $tokenQuantity)
    {
        $this->mintmeAddress = $mintmeAddress;
        $this->tokenPrecision = $tokenPrecision;
        $this->tokenQuantity = $tokenQuantity;
    }

    public function getMintmeAddress(): string
    {
        return $this->mintmeAddress;
    }

    public function getTokenPrecision(): int
    {
        return $this->tokenPrecision;
    }

    public function getTokenQuantity(): string
    {
        return $this->tokenQuantity;
    }
}
