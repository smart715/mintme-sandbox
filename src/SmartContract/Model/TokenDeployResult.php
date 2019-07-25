<?php declare(strict_types = 1);

namespace App\SmartContract\Model;

/** @codeCoverageIgnore */
class TokenDeployResult
{
    /** @var string */
    private $address;

    public function __construct(string $address)
    {
        $this->address = $address;
    }

    public function getAddress(): string
    {
        return $this->address;
    }
}
