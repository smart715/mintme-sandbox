<?php declare(strict_types = 1);

namespace App\SmartContract\Config;

/** @codeCoverageIgnore */
class Config
{
    /** @var string */
    private $mintmeAddress;

    /** @var string */
    private $tokenQuantity;

    public function __construct(string $mintmeAddress, string $tokenQuantity)
    {
        $this->mintmeAddress = $mintmeAddress;
        $this->tokenQuantity = $tokenQuantity;
    }

    public function getMintmeAddress(): string
    {
        return $this->mintmeAddress;
    }

    public function getTokenQuantity(): string
    {
        return $this->tokenQuantity;
    }
}
