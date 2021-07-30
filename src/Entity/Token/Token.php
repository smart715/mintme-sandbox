<?php declare(strict_types = 1);

namespace App\Entity\Token;

use App\Entity\AirdropCampaign\Airdrop;
use App\Entity\Crypto;
use App\Entity\DiscordRole;
use App\Entity\Image;
use App\Entity\ImagineInterface;
use App\Entity\Message\Thread;
use App\Entity\Post;
use App\Entity\Profile;
use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Entity\UserToken;
use App\Utils\Symbols;
use App\Validator\Constraints as AppAssert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Money\Currency;
use Money\Money;
use phpDocumentor\Reflection\Types\This;
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
class Token implements TradebleInterface, ImagineInterface
{
    public const NAME_MIN_LENGTH = 4;
    public const NAME_MAX_LENGTH = 60;
    public const DESC_MIN_LENGTH = 200;
    public const DESC_MAX_LENGTH = 10000;
    public const NOT_DEPLOYED = 'not-deployed';
    public const DEPLOYED = 'deployed';
    public const PENDING = 'pending';
    public const TOKEN_SUBUNIT = 4;
    public const PENDING_ADDR = '0x';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Assert\NotBlank()
     * @Assert\Regex(pattern="/^[a-zA-Z0-9\s-]*$/", message="Invalid token name.")
     * @Assert\Length(min = Token::NAME_MIN_LENGTH, max = Token::NAME_MAX_LENGTH)
     * @AppAssert\DashedUniqueName(message="Token name is already exists.")
     * @AppAssert\IsNotBlacklisted(type="token", message="Forbidden token name, please try another")
     * @AppAssert\DisallowedWord()
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
     * @Groups({"API", "API_TOK"})
     * @var string|null
     */
    protected $txHash;

    /**
     * @ORM\Column(type="bigint",nullable=true)
     */
    protected ?string $fee = null; // phpcs:ignore

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
     * @Assert\Regex(
      *     pattern="/^https:\/\/t\.me\/joinchat\/([-\w]{1,})$/",
      *     match=true,
      *     message="Invalid telegram link"
      * )
     * @var string|null
     */
    protected $telegramUrl;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Url()
     * @Assert\Regex(
      *     pattern="/^https:\/\/(discord\.gg|discordapp\.com\/invite)\/([-\w]{1,})$/",
      *     match=true,
      *     message="Invalid discord link"
      * )
     * @var string|null
     */
    protected $discordUrl;

    /**
     * @ORM\Column(type="text", length=Token::DESC_MAX_LENGTH, nullable=true)
     * @AppAssert\TokenDescription(
     *     min = Token::DESC_MIN_LENGTH,
     *     max = Token::DESC_MAX_LENGTH
     * )
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
     * @ORM\ManyToOne(targetEntity="App\Entity\Profile", inversedBy="tokens")
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

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Crypto", cascade={"all"})
     * @ORM\JoinColumn(nullable=true)
     * @var Crypto|null
     */
    protected $crypto;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Crypto", cascade={"all"})
     * @ORM\JoinColumn(nullable=true)
     * @var Crypto|null
     */
    protected $exchangeCrypto;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @Groups({"API_TOK"})
     * @var \DateTimeImmutable
     */
    protected $created;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\UserToken",
     *     mappedBy="token",
     *     fetch="EXTRA_LAZY")
     * @var ArrayCollection|null
     */
    protected $users;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     * @var \DateTimeImmutable|null
     */
    private $deployedDate;

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default"=false})
     */
    private bool $deployed = false; // phpcs:ignore

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    protected $withdrawn = '0';

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Image", cascade={"remove"}, orphanRemoval=true)
     * @ORM\JoinColumn(name="image_id", referencedColumnName="id")
     * @Groups({"Default", "API"})
     * @var Image|null
     */
    protected $image;

     /**
     * @ORM\Column(type="string", nullable=true)
     * @var string|null
     */
    private $mintedAmount;

    /**
     * @ORM\OneToMany(
     *     targetEntity="App\Entity\AirdropCampaign\Airdrop",
     *     mappedBy="token",
     *     orphanRemoval=true,
     *     fetch="EXTRA_LAZY"
     * )
     * @var ArrayCollection
     */
    private $airdrops;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    protected $airdropsAmount = '0';

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Post", mappedBy="token")
     * @ORM\OrderBy({"createdAt" = "DESC"})
     * @var ArrayCollection
     */
    protected $posts;

    /**
     * @ORM\Column(type="boolean", nullable=false)
     * @var bool
     */
    protected $isBlocked = false;

    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected bool $isHidden = false; // phpcs:ignore

    /**
     * @ORM\Column(name="number_of_reminder", type="smallint")
     * @var int
     */
    private $numberOfReminder = 0;

    /**
     * @ORM\Column(name="next_reminder_date", type="datetime", nullable=true)
     * @var \DateTime
     */
    private $nextReminderDate;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Message\Thread", mappedBy="token", cascade={"persist", "remove"})
     * @var ArrayCollection
     */
    private $threads;

    /**
     * @ORM\Column(type="integer", options={"default"=12})
     * @Groups({"Default", "API"})
     * @var int|null
     */
    private $decimals = 12;

    /**
     * @ORM\OneToMany(
     *     targetEntity="App\Entity\Voting\TokenVoting",
     *     mappedBy="token"
     * )
     * @ORM\OrderBy({"endDate" = "DESC", "createdAt" = "DESC"})
     *  @var ArrayCollection
     */
    private $votings;

    /**
     * @ORM\Column(type="boolean", options={"default" : false}, nullable= false)
     */
    private bool $showDeployedModal = false; // phpcs:ignore

    /**
     * @ORM\OneToOne(targetEntity="DiscordConfig", mappedBy="token", cascade={"persist", "remove"})
     */
    private ?DiscordConfig $discordConfig;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\DiscordRole", mappedBy="token", cascade={"remove"})
     */
    protected PersistentCollection $discordRoles;

    public function __construct()
    {
        $this->airdrops = new ArrayCollection();
    }

    /** @return User[] */
    public function getUsers(): array
    {
        return array_map(function (UserToken $userToken) {
            return $userToken->getUser();
        }, $this->users->toArray());
    }

    /**
     * @Groups({"Default", "API"})
     * @return int
     */
    public function getHoldersCount(): int
    {
        if ($this->users) {
            $holders = $this->users->filter(function ($userToken) {
                return $userToken->isHolder();
            });

            return $holders->count();
        }

        return 0;
    }

    /** {@inheritdoc} */
    public function getSymbol(): string
    {
        return $this->getName();
    }

    public function setSymbol(string $symbol): self
    {
        return $this->setName($symbol);
    }

    public function getCrypto(): ?Crypto
    {
        return $this->crypto;
    }

    /**
     * @Groups({"API"})
     */
    public function getCryptoSymbol(): string
    {
        return $this->crypto
            ? $this->crypto->getSymbol()
            : Symbols::WEB;
    }

    public function setCrypto(?Crypto $crypto): self
    {
        $this->crypto = $crypto;

        return $this;
    }

    public function getExchangeCryptoSymbol(): string
    {
        return $this->exchangeCrypto
            ? $this->exchangeCrypto->getSymbol()
            : Symbols::WEB;
    }

    public function setExchangeCrypto(?Crypto $crypto): self
    {
        $this->exchangeCrypto = $crypto;

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

    public function getFee(): ?Money
    {
        return $this->fee ?
            new Money($this->fee, new Currency(Symbols::TOK))
            : null;
    }

    public function setFee(?Money $fee): self
    {
        $this->fee = $fee
            ? $fee->getAmount()
            : null;

        return $this;
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

    public function setDeployCost(?string $cost): self
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

    /**
     * @Groups({"API", "dev"})
     */
    public function getDeploymentStatus(): string
    {
        return !$this->address
            ? self::NOT_DEPLOYED
            : (self::PENDING_ADDR === $this->address
                ? self::PENDING
                : self::DEPLOYED);
    }

    public function isDeployed(): bool
    {
        return self::DEPLOYED === $this->getDeploymentStatus();
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

    /** @Groups({"Default", "API"}) */
    public function getTelegramUrl(): ?string
    {
        return $this->telegramUrl;
    }

    public function setTelegramUrl(?string $url): self
    {
        $this->telegramUrl = $url;

        return $this;
    }

    /** @Groups({"Default", "API"}) */
    public function getDiscordUrl(): ?string
    {
        return $this->discordUrl;
    }

    public function setDiscordUrl(?string $url): self
    {
        $this->discordUrl = $url;

        return $this;
    }

    /** @codeCoverageIgnore */
    public function getDeployedDate(): ?\DateTimeImmutable
    {
        return $this->deployedDate;
    }

    /** @codeCoverageIgnore */
    public function setDeployedDate(?\DateTimeImmutable $deployedDate): self
    {
        $this->deployedDate = $deployedDate;

        return $this;
    }

    /** @codeCoverageIgnore */
    public function getDeployed(): bool
    {
        return $this->deployed;
    }

    /** @codeCoverageIgnore */
    public function setDeployed(bool $deployed): self
    {
        $this->deployed = $deployed;

        return $this;
    }

    /** @codeCoverageIgnore */
    public function getWithdrawn(): string
    {
        return $this->withdrawn;
    }

    /** @codeCoverageIgnore */
    public function setWithdrawn(string $withdrawn): self
    {
        $this->withdrawn = $withdrawn;

        return $this;
    }

    public function setImage(Image $image): void
    {
        $this->image = $image;
    }

    public function getImage(): ?Image
    {
        if ($this->image) {
            return $this->image;
        }

        return Symbols::WEB === $this->getCryptoSymbol()
            ? Image::defaultImage(Image::DEFAULT_TOKEN_IMAGE_URL)
            : null;
    }

    public function getMintedAmount(): Money
    {
        return new Money($this->mintedAmount ?? 0, new Currency(Symbols::TOK));
    }

    public function setMintedAmount(Money $mintedAmount): void
    {
        $this->mintedAmount = $mintedAmount->getAmount();
    }

    /** @codeCoverageIgnore */
    public function getAirdrops(): Collection
    {
        return $this->airdrops;
    }

    /** @codeCoverageIgnore */
    public function getActiveAirdrop(): ?Airdrop
    {
        $activeAirdrop = $this->getAirdrops()->filter(function (Airdrop $airdrop) {
            return Airdrop::STATUS_ACTIVE === $airdrop->getStatus();
        });

        return $activeAirdrop->isEmpty()
            ? null
            : $activeAirdrop->first();
    }

    public function getAirdrop(int $id): ?Airdrop
    {
        $airdrops = $this->getAirdrops()->filter(fn(Airdrop $a) => $id === $a->getId());

        return $airdrops->isEmpty()
            ? null
            : $airdrops->first();
    }

    /** @codeCoverageIgnore */
    public function addAirdrop(Airdrop $airdrop): self
    {
        if (!$this->airdrops->contains($airdrop)) {
            $this->airdrops->add($airdrop);
            $airdrop->setToken($this);
        }

        return $this;
    }

    /** @codeCoverageIgnore */
    public function removeAirdrop(Airdrop $airdrop): self
    {
        if ($this->airdrops->contains($airdrop)) {
            $this->airdrops->removeElement($airdrop);
        }

        return $this;
    }

    /** @codeCoverageIgnore */
    public function getAirdropsAmount(): Money
    {
        return new Money($this->airdropsAmount, new Currency(Symbols::TOK));
    }

    /** @codeCoverageIgnore */
    public function setAirdropsAmount(Money $airdropsAmount): self
    {
        $this->airdropsAmount = $airdropsAmount->getAmount();

        return $this;
    }

    /** @Groups({"Default", "API", "dev"}) */
    public function isBlocked(): bool
    {
        return $this->isBlocked;
    }

    public function setIsBlocked(bool $isBlocked): self
    {
        $this->isBlocked = $isBlocked;

        return $this;
    }

    public function isHidden(): bool
    {
        return $this->isHidden;
    }

    public function setIsHidden(bool $isHidden): self
    {
        $this->isHidden = $isHidden;

        return $this;
    }

    /**
     * @return Post[]
     */
    public function getPosts(): array
    {
        return $this->posts->toArray();
    }

    public function getOwner(): ?User
    {
        $profile = $this->getProfile();

        return $profile
            ? $profile->getUser()
            : null;
    }

    /** @Groups({"Default", "API"}) */
    public function getOwnerId(): ?int
    {
        $owner = $this->getOwner();

        return $owner
            ? $owner->getId()
            : null;
    }

    public function getNumberOfReminder(): ?int
    {
        return $this->numberOfReminder;
    }

    public function setNumberOfReminder(int $numberOfReminder): self
    {
        $this->numberOfReminder = $numberOfReminder;

        return $this;
    }

    public function getNextReminderDate(): ?\DateTime
    {
        return $this->nextReminderDate;
    }

    public function setNextReminderDate(\DateTime $nextReminderDate): self
    {
        $this->nextReminderDate = $nextReminderDate;

        return $this;
    }

    public function addThread(Thread $thread): self
    {
        $this->threads[] = $thread;

        return $this;
    }

    public function getThreads(): array
    {
        return $this->threads->toArray();
    }

    public function setDecimals(int $decimals): self
    {
        $this->decimals = $decimals;

        return $this;
    }

    public function getDecimals(): ?int
    {
        return $this->decimals;
    }

    /*
     * @Groups({"Default"})
     */
    public function isMintmeToken(): bool
    {
        return Symbols::WEB === $this->getCryptoSymbol();
    }

    /*
     * @Groups({"Default"})
     */
    public function isControlledToken(): bool
    {
        return !$this->isDeployed() || ($this->getAddress() && $this->getTxHash());
    }

    public function isOwner(array $ownTokens): bool
    {
        /** @var Token $ownToken */
        foreach ($ownTokens as $ownToken) {
            if ($ownToken->getId() === $this->getId()) {
                return true;
            }
        }

        return false;
    }

    public function isShowDeployedModal(): bool
    {
        return $this->showDeployedModal;
    }

    public function setShowDeployedModal(bool $showDeployedModal): self
    {
        $this->showDeployedModal = $showDeployedModal;

        return $this;
    }

    public function setTxHash(?string $txHash): self
    {
        $this->txHash = $txHash;

        return $this;
    }

    public function getTxHash(): ?string
    {
        return $this->txHash;
    }

    public function getVotings(): array
    {
        return $this->votings->toArray();
    }

    public function getDiscordConfig(): DiscordConfig
    {
        return $this->discordConfig ?? $this->discordConfig = (new DiscordConfig())->setToken($this);
    }

    public function getDiscordRoles(): PersistentCollection
    {
        return $this->discordRoles;
    }

    public function getDiscordRolesMatching(Criteria $criteria): Collection
    {
        return $this->discordRoles->matching($criteria);
    }

    public function addDiscordRole(DiscordRole $role): self
    {
        $this->discordRoles->add($role);

        return $this;
    }

    public function removeDiscordRole(DiscordRole $role): self
    {
        $this->discordRoles->removeElement($role);

        return $this;
    }
}
