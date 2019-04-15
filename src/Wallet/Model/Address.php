<?php declare(strict_types = 1);

namespace App\Wallet\Model;

use Symfony\Component\Serializer\Annotation\Groups;

class Address
{
    /**
     * @var string
     * @Groups({"API"})
     */
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
