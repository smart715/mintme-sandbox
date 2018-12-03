<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CryptoRepository")
 * @UniqueEntity("name")
 * @UniqueEntity("symbol")
 */
class Crypto
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=20)
     * @var string
     */
    protected $name;

    /**
     * @ORM\Column(type="string", length=5)
     * @var string
     */
    protected $symbol;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $subunit;

    /**
     * @ORM\Column(type="bigint")
     * @var string
     */
    protected $fee;

    public function getName(): string
    {
        return $this->name;
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function getSubunit(): int
    {
        return $this->subunit;
    }

    public function getFee(): Money
    {
        return new Money($this->fee, new Currency($this->getSymbol()));
    }
}
