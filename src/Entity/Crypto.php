<?php declare(strict_types = 1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CryptoRepository")
 * @UniqueEntity("name")
 * @UniqueEntity("symbol")
 * @codeCoverageIgnore
 */
class Crypto implements TradebleInterface
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
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $showSubunit;

    /**
     * @ORM\Column(type="bigint")
     * @var string
     */
    protected $fee;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $tradable;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $exchangeble;

    /**
     * @ORM\OneToMany(targetEntity="UserCrypto", mappedBy="crypto")
     * @var ArrayCollection
     */
    protected $relatedUsers;

    public function getId(): int
    {
        return $this->id;
    }

    /** @return User[] */
    public function getRelatedUsers(): array
    {
        return array_map(function (UserCrypto $userCrypto) {
            return $userCrypto->getUser();
        }, $this->relatedUsers->toArray());
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return $this->name;
    }

    /** {@inheritdoc} */
    public function getSymbol(): string
    {
        return $this->symbol;
    }

    /** @Groups({"API"}) */
    public function getSubunit(): int
    {
        return $this->subunit;
    }

    public function getShowSubunit(): int
    {
        return $this->showSubunit;
    }

    /** Show if crypto could be used as `base` currency */
    /** @Groups({"API"}) */
    public function isTradable(): bool
    {
        return $this->tradable;
    }

    /** Show if crypto could be used as `quote` currency */
    /** @Groups({"API"}) */
    public function isExchangeble(): bool
    {
        return $this->exchangeble;
    }

    public function getFee(): Money
    {
        return new Money($this->fee, new Currency($this->getSymbol()));
    }
}
