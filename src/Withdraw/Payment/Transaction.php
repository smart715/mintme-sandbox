<?php

namespace App\Withdraw\Payment;

class Transaction
{
    /** @var string */
    private $hash;

    /** @var string */
    private $key;

    /** @var Status */
    private $status;

    public function __construct(string $hash, string $key, Status $status)
    {
        $this->hash = $hash;
        $this->key = $key;
        $this->status = $status;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }
}
