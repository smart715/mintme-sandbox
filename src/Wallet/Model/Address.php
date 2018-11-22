<?php

namespace App\Wallet\Model;

class Address
{
    /** @var string */
    private $address;

    public function __construct(string $address)
    {
        if (!preg_match('/^\w+$/', $address)) {
            throw new \InvalidArgumentException('Incorrect address');
        }

        $this->address = $address;
    }

    public function getAddress(): string
    {
        return $this->address;
    }
}
