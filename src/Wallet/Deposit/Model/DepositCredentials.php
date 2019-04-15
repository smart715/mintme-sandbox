<?php declare(strict_types = 1);

namespace App\Wallet\Deposit\Model;

use App\Entity\Token\Token;

class DepositCredentials implements \IteratorAggregate
{
    /** @var mixed[]*/
    private $addresses;

    public function __construct(array $addresses)
    {
        $this->addresses = $addresses;
    }

    public function toArray(): array
    {
        return $this->addresses;
    }

    public function getAddress(string $symbol): string
    {
        return $this->addresses[$symbol];
    }

    /** {@inheritdoc} */
    public function getIterator()
    {
        return new \ArrayIterator($this->toArray());
    }
}
