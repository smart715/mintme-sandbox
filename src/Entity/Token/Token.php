<?php declare(strict_types = 1);

namespace App\Entity\Token;

use App\Entity\Crypto;
use App\Entity\Profile;
use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Entity\UserToken;
use App\Validator\Constraints as AppAssert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TokenRepository")
 * @UniqueEntity("name", message="Token name is already taken")
 * @UniqueEntity("address")
 * @ORM\HasLifecycleCallbacks()
 * @codeCoverageIgnore
 */
class Token implements TradebleInterface
{
    public const WEB_SYMBOL = "WEB";
    public const BTC_SYMBOL = "BTC";
    public const NAME_MIN_LENGTH = 4;
    public const NAME_MAX_LENGTH = 60;
    public const NOT_DEPLOYED = 'not-deployed';
    public const DEPLOYED = 'deployed';
    public const PENDING = 'pending';

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
     * @Assert\Regex(pattern="/^[a-zA-Z0-9\-\s]*$/", message="Invalid token name.")
     * @Assert\Length(min = Token::NAME_MIN_LENGTH, max = Token::NAME_MAX_LENGTH)
     * @AppAssert\DashedUniqueName(message="Token name is already exists.")
     * @AppAssert\IsNotBlacklisted(type="token", message="This value is not allowed")
     * @Groups({"API", "API_TOK"})
     * @var string
     */
    protected $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"API_TOK"})
     * @var string|null
     */
    protected $address;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @var string|null
     */
    protected $deployCost;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @var string|null
     */
    protected $mintDestination;

    /**
     * @ORM\Column(type="boolean", options={"default": 0})
     * @var bool
     */
    protected $mintDestinationLocked = false;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Url()
     * @Groups({"API_TOK"})
     * @var string|null
     */
    protected $websiteUrl;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @AppAssert\IsUrlFromDomain("www.facebook.com")
     * @Groups({"API_TOK"})
     * @var string|null
     */
    protected $facebookUrl;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"API_TOK"})
     * @var string|null
     */
    protected $youtubeChannelId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Url()
     * @var string|null
     */
    protected $telegramUrl;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Url()
     * @var string|null
     */
    protected $discordUrl;

    /**
     * @ORM\Column(type="string", length=60000, nullable=true)
     * @Groups({"API_TOK"})
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
     * @Groups({"API_TOK"})
     * @var Profile|null
     */
    protected $profile;

    /**
     * @ORM\OneToOne(
     *     targetEntity="App\Entity\Token\LockIn",
     *     mappedBy="token",
     *     orphanRemoval=true,
     *     cascade={"persist", "remove"}
     *     )
     * @var LockIn|null
     */
    protected $lockIn;

    /** @var Crypto|null */
    protected $crypto;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @Groups({"API_TOK"})
     * @var \DateTimeImmutable
     */
    protected $created;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\UserToken", mappedBy="token")
     * @var ArrayCollection
     */
    protected $users;

    /** @return User[] */
    public function getUsers(): array
    {
        return array_map(function (UserToken $userToken) {
            return $userToken->getUser();
        }, $this->users->toArray());
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

    public function setPendingDeployment(): self
    {
        $this->address = '0x';

        return $this;
    }

    public function setUpdatingMintDestination(): self
    {
        $this->mintDestination = '0x';

        return $this;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function setDeployCost(string $cost): self
    {
        $this->deployCost = $cost;

        return $this;
    }

    public function getDeployCost(): ?string
    {
        return $this->deployCost;
    }

    public function getMintDestination(): ?string
    {
        return $this->mintDestination;
    }

    public function setMintDestination(string $mintDestination): self
    {
        $this->mintDestination = $mintDestination;

        return $this;
    }

    public function isMintDestinationLocked(): bool
    {
        return $this->mintDestinationLocked;
    }

    public function lockMintDestination(): self
    {
        $this->mintDestinationLocked = true;

        return $this;
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

    public function setFacebookUrl(?string $facebookUrl): self
    {
        $this->facebookUrl = $facebookUrl;

        return $this;
    }

    public function getYoutubeChannelId(): ?string
    {
        return $this->youtubeChannelId;
    }

    public function setYoutubeChannelId(?string $youtubeChannelId): self
    {
        $this->youtubeChannelId = $youtubeChannelId;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description ?? '';
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

    public function deploymentStatus(): string
    {
        return !$this->address
            ? self::NOT_DEPLOYED
            : ('0x' === $this->address
                ? self::PENDING
                : self::DEPLOYED);
    }

    public static function getFromCrypto(Crypto $crypto): self
    {
        return (new self())->setName($crypto->getSymbol());
    }

    public static function getFromSymbol(string $symbol): self
    {
        return (new self())->setName($symbol);
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

    public function getTelegramUrl(): ?string
    {
        return $this->telegramUrl;
    }

    public function setTelegramUrl(?string $url): self
    {
        $this->telegramUrl = $url;

        return $this;
    }

    public function getDiscordUrl(): ?string
    {
        return $this->discordUrl;
    }

    public function setDiscordUrl(?string $url): self
    {
        $this->discordUrl = $url;

        return $this;
    }
}
