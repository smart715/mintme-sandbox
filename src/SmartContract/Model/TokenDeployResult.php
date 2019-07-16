<?php declare(strict_types = 1);

namespace App\SmartContract\Model;

class TokenDeployResult
{
    /** @var string */
    private $address;

    /** @var string */
    private $transactionHash;

    public function __construct(string $address, string $transactionHash)
    {
        $this->address = $address;
        $this->transactionHash = $transactionHash;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getTransactionHash(): string
    {
        return $this->transactionHash;
    }
}
