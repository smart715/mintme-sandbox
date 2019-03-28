<?php declare(strict_types = 1);

namespace App\Entity\Token;

use App\Entity\Crypto;
use App\Entity\Profile;
use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Validator\Constraints as AppAssert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TokenRepository")
 * @UniqueEntity("name")
 * @UniqueEntity("address")
 * @ORM\HasLifecycleCallbacks()
 */
class Token implements TradebleInterface
{
    public const WEB_SYMBOL = "WEB";
    public const BTC_SYMBOL = "BTC";

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     * @Assert\Regex("/^[a-zA-Z0-9 ]+$/")
     * @Assert\Length(min = 4, max = 255)
     * @Groups({"API"})
     * @var string
     */
    protected $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @var string|null
     */
    protected $address;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Url()
     * @var string|null
     */
    protected $websiteUrl;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @AppAssert\IsUrlFromDomain("www.facebook.com")
     * @var string|null
     */
    protected $facebookUrl;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @var string|null
     */
    protected $youtubeChannelId;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string|null
     */
    protected $description;

    /**
     * @ORM\Column(type="string", length=180, nullable=true)
     * @var string|null
     */
    protected $websiteConfirmationToken;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Profile", inversedBy="token")
     * @var Profile|null
     */
    protected $profile;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Token\LockIn", mappedBy="token")
     * @var LockIn|null
     */
    protected $lockIn;

    /** @var Crypto|null */
    protected $crypto;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @var \DateTimeImmutable
     */
    protected $created;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\User", mappedBy="relatedTokens")
     * @var ArrayCollection
     */
    protected $relatedUsers;

    /** @return User[] */
    public function getRelatedUsers(): array
    {
        return $this->relatedUsers->toArray();
    }

    /** {@inheritdoc} */
    public function getSymbol(): string
    {
        return $this->getName();
    }

    public function getCrypto(): ?Crypto
    {
        return $this->crypto;
    }

    public function setCrypto(?Crypto $crypto): self
    {
        $this->crypto = $crypto;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLockIn(): ?LockIn
    {
        return $this->lockIn;
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        /** @var string|null $name */
        $name = $this->name;

        return (string)$name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function getWebsiteUrl(): ?string
    {
        return $this->websiteUrl;
    }

    public function setWebsiteUrl(string $websiteUrl): self
    {
        $this->websiteUrl = $websiteUrl;

        return $this;
    }

    public function getFacebookUrl(): ?string
    {
        return $this->facebookUrl;
    }

    public function setFacebookUrl(string $facebookUrl): self
    {
        $this->facebookUrl = $facebookUrl;

        return $this;
    }

    public function getYoutubeChannelId(): ?string
    {
        return $this->youtubeChannelId;
    }

    public function setYoutubeChannelId(string $youtubeChannelId): self
    {
        $this->youtubeChannelId = $youtubeChannelId;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getWebsiteConfirmationToken(): ?string
    {
        return $this->websiteConfirmationToken;
    }

    public function setWebsiteConfirmationToken(string $websiteConfirmationToken): self
    {
        $this->websiteConfirmationToken = $websiteConfirmationToken;

        return $this;
    }

    public function setProfile(Profile $profile): self
    {
        $this->profile = $profile;

        return $this;
    }

    public function getProfile(): ?Profile
    {
        return $this->profile;
    }

    public static function getFromCrypto(Crypto $crypto): self
    {
        return (new self())->setName($crypto->getSymbol());
    }

    public function getCreated(): \DateTimeImmutable
    {
        return $this->created;
    }

    /** @ORM\PrePersist() */
    public function setCreatedValue(): self
    {
        $this->created = new \DateTimeImmutable();

        return $this;
    }
}
