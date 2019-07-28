<?php declare(strict_types = 1);

namespace App\SmartContract\Config;

/** @codeCoverageIgnore */
class Config
{
    /** @var string */
    private $mintmeAddress;

    /** @var string */
    private $tokenQuantity;

    /** @var float */
    private $depositFee;

    /** @var float */
    private $withdrawFee;

    public function __construct(string $mintmeAddress, string $tokenQuantity, float $depositFee, float $withdrawFee)
    {
        $this->mintmeAddress = $mintmeAddress;
        $this->tokenQuantity = $tokenQuantity;
        $this->depositFee = $depositFee;
        $this->withdrawFee = $withdrawFee;
    }

    public function getMintmeAddress(): string
    {
        return $this->mintmeAddress;
    }

    public function getTokenQuantity(): string
    {
        return $this->tokenQuantity;
    }

    public function getWithdrawFee(): float
    {
        return $this->withdrawFee;
    }

    public function getDepositFee(): float
    {
        return $this->depositFee;
    }
}
