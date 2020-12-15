<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\Token\Token;
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
class Crypto implements TradebleInterface, ImagineInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    protected int $id;

    /**
     * @ORM\Column(type="string", length=20)
     */
    protected string $name;

    /**
     * @ORM\Column(type="string", length=5)
     */
    protected string $symbol;

    /**
     * @ORM\Column(type="integer")
     */
    protected int $subunit;

    /**
     * @ORM\Column(type="integer")
     */
    protected int $showSubunit;

    /**
     * @ORM\Column(type="bigint")
     */
    protected string $fee;

    /**
     * @ORM\Column(type="boolean")
     */
    protected bool $tradable;

    /**
     * @ORM\Column(type="boolean")
     */
    protected bool $exchangeble;

    /**
     * @ORM\OneToMany(targetEntity="UserCrypto", mappedBy="crypto", cascade={"persist", "remove"})
     * @var ArrayCollection
     */
    protected $users;

    /**
     * @ORM\Column(type="string", options={"default" : ""})
     * @var string
     */
    protected $imagePath = '';

    /**
     * @ORM\Column(type="boolean", options={"default" : false}, nullable=false)
     * @Groups({"Default", "API"})
     * @var bool
     */
    protected $isToken = false;

    /**
     * @Groups({"Default", "API"})
     */
    protected Image $image;

    public function getId(): int
    {
        return $this->id;
    }

    /** @return User[] */
    public function getUsers(): array
    {
        return array_map(function (UserCrypto $userCrypto) {
            return $userCrypto->getUser();
        }, $this->users->toArray());
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function setSymbol(string $symbol): void
    {
        $this->symbol = $symbol;
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

    public function setImage(Image $image): void
    {
        $this->image = $image;
    }

    public function getImage(): Image
    {
        return Image::defaultImage($this->imagePath);
    }

    public function isToken(): bool
    {
        return $this->isToken;
    }

    public function setIsToken(bool $isToken): void
    {
        $this->isToken = $isToken;
    }
}
