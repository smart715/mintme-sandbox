<?php declare(strict_types = 1);

namespace App\Entity\Token;

use App\Entity\AirdropCampaign\Airdrop;
use App\Entity\Crypto;
use App\Entity\DeployNotification;
use App\Entity\DiscordRole;
use App\Entity\Image;
use App\Entity\ImagineInterface;
use App\Entity\Message\Thread;
use App\Entity\Post;
use App\Entity\Profile;
use App\Entity\TokenCrypto;
use App\Entity\TokenSignupBonusCode;
use App\Entity\TokenSignupHistory;
use App\Entity\TradableInterface;
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
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TokenRepository")
 * @UniqueEntity("name", message="Token name is already taken")
 * @ORM\HasLifecycleCallbacks()
 * @codeCoverageIgnore
 */
class Token implements TradableInterface, ImagineInterface
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
    public const TRADABLE_TYPE = 'token';

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
     * @Groups({"API", "API_TOK", "API_BASIC"})
     * @var string
     */
    protected $name;

    /**
     * @ORM\Column(type="bigint",nullable=true)
     */
    protected ?string $fee = null; // phpcs:ignore

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @var string|null
     */
    protected $mintDestination;

    /**
     * @ORM\Column(type="string", length=2048, nullable=true)
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
     * @AppAssert\IsUrlFromDomain("www.twitter.com")
     * @Groups({"API_TOK"})
     */
    protected ?string $twitterUrl = null; // phpcs:ignore

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
      *     pattern="/^https:\/\/(?:t|telegram)\.(?:me|dog)\/(joinchat\/|\+)?([\w-]+)$/",
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
      *     pattern="/^https:\/\/(discord\.gg|(discordapp|discord)\.com\/invite)\/([-\w]{1,})$/",
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
     * @ORM\OneToMany(targetEntity="App\Entity\TokenCrypto",
     *     mappedBy="token",
     *     fetch="EXTRA_LAZY",
     *     cascade={"persist", "remove"}
     *  )
     */
    protected Collection $exchangeCryptos;

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
     * @ORM\Column(type="boolean", nullable=false, options={"default"=false})
     */
    private bool $deployed = false; // phpcs:ignore

    /**
     * @ORM\OneToMany(
     *      targetEntity="App\Entity\Token\TokenDeploy",
     *      cascade={"persist", "remove"},
     *      mappedBy="token",
     *      fetch="EXTRA_LAZY"
     *  )
     * @ORM\OrderBy({"createdAt" = "ASC"})
     */
    protected Collection $deploys;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    protected $withdrawn = '0';

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Image", cascade={"remove"}, orphanRemoval=true)
     * @ORM\JoinColumn(name="image_id", referencedColumnName="id")
     * @Groups({"Default", "API", "API_BASIC"})
     */
    protected ?Image $image = null; // phpcs:ignore

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Image", cascade={"remove"}, orphanRemoval=true)
     * @ORM\JoinColumn(name="cover_image_id", referencedColumnName="id")
     * @Groups({"Default", "API"})
     */
    protected ?Image $coverImage = null; // phpcs:ignore

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
     * @ORM\OneToMany(targetEntity="App\Entity\Post", mappedBy="token", cascade={"remove"})
     * @ORM\OrderBy({"createdAt" = "DESC"})
     * @var ArrayCollection
     */
    protected $posts;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TokenSignupHistory", mappedBy="token", cascade={"remove"})
     */
    protected Collection $tokenSignupHistory; // phpcs:ignore

    /**
     * @ORM\Column(type="boolean", nullable=false)
     * @var bool
     */
    protected $isBlocked = false;

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default"=false})
     */
    protected bool $isQuiet = false; // phpcs:ignore

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default"=true})
     * @Groups({"Default", "API"})
     */
    protected bool $createdOnMintmeSite = true; // phpcs:ignore

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
     * @ORM\Column(name="next_reminder_date", type="datetime_immutable", nullable=true)
     */
    private \DateTimeImmutable $nextReminderDate;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Message\Thread", mappedBy="token", cascade={"persist", "remove"})
     */
    private Collection $threads;

    /**
     * @ORM\Column(type="integer", options={"default"=12})
     * @Groups({"Default", "API"})
     * @var int|null
     */
    private $decimals = 12;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $priceDecimals = null; // phpcs:ignore

    /**
     * @ORM\OneToMany(
     *     targetEntity="App\Entity\Voting\TokenVoting",
     *     mappedBy="token"
     * )
     * @ORM\OrderBy({"createdAt" = "DESC", "endDate" = "DESC"})
     */
    private Collection $votings;

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

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\CommentTip", mappedBy="token", cascade={"remove"})
    */
    private ?Collection $tips; // phpcs:ignore

    /**
     * @ORM\Column(type="string", nullable=false, options={"default": 1000})
     */
    protected string $tokenProposalMinAmount = '1000'; // phpcs:ignore

    /**
     * @ORM\Column(type="string", nullable=false, options={"default": 1000})
     */
    protected string $dmMinAmount = '1000'; // phpcs:ignore

    /**
     * @ORM\Column(type="string", nullable=false, options={"default": 1000})
     */
    protected string $commentMinAmount = '1000'; // phpcs:ignore

    /**
     * @ORM\Column(type="boolean", options={"default": false}, nullable=false)
     */
    protected bool $hasTax = false; // phpcs:ignore

    /**
     * @ORM\Column(type="boolean", options={"default": false}, nullable=false)
     */
    protected bool $isPausable = false; // phpcs:ignore

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Token\TokenRank", inversedBy="token")
     */
    private ?TokenRank $rank;

    /**
    * @ORM\OneToOne(targetEntity=TokenSignupBonusCode::class, mappedBy="token", cascade={"persist", "remove"}, fetch="EAGER")
    */
    private ?TokenSignupBonusCode $signUpBonusCode;

    /**
     * @ORM\Column(type="boolean", options={"default": false}, nullable=false)
     */
    protected bool $depositsDisabled = false; // phpcs:ignore

    /**
     * @ORM\Column(type="boolean", options={"default": false}, nullable=false)
     */
    protected bool $withdrawalsDisabled = false; // phpcs:ignore

    /**
     * @ORM\Column(type="boolean", options={"default": false}, nullable=false)
     */
    protected bool $tradesDisabled = false; // phpcs:ignore

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Token\TokenPromotion", mappedBy="token", fetch="EXTRA_LAZY")
     */
    protected Collection $promotions;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\DeployNotification", mappedBy="token", fetch="EXTRA_LAZY")
     */
    protected Collection $deployNotifications;

    public function __construct()
    {
        $this->exchangeCryptos = new ArrayCollection();
        $this->deploys = new ArrayCollection();
        $this->airdrops = new ArrayCollection();
        $this->threads = new ArrayCollection();
        $this->votings = new ArrayCollection();
        $this->rank = null;
        $this->tips = new ArrayCollection();
        $this->tokenSignupHistory = new ArrayCollection();
        $this->promotions = new ArrayCollection();
        $this->deployNotifications = new ArrayCollection();
    }

    public static function getTradableType(): string
    {
        return self::TRADABLE_TYPE;
    }

    /** @return User[] */
    public function getUsers(): array
    {
        return array_map(function (UserToken $userToken) {
            return $userToken->getUser();
        }, $this->users->toArray());
    }

    /** @return UserToken[] */
    public function getHolders(): array
    {
        return $this->users->toArray();
    }

    /**
     * Avoid adding this property to group serializer due to performance
     */
    public function getHoldersCount(): int
    {
        if (!$this->users) {
            return 0;
        }

        $expr = Criteria::expr();
        $criteria = Criteria::create()
            ->where($expr->eq('isHolder', true));

        return $this->users->matching($criteria)->count();
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
        $mainDeploy = $this->getMainDeploy();

        return $mainDeploy
            ? $mainDeploy->getCrypto()
            : null;
    }

    /**
     * @Groups({"API", "API_BASIC"})
     */
    public function getCryptoSymbol(): string
    {
        $crypto = $this->getCrypto();

        return $crypto
            ? $crypto->getSymbol()
            : Symbols::WEB;
    }

    public function getMoneySymbol(): string
    {
        return Symbols::TOK;
    }

    public function getExchangeCryptos(): Collection
    {
        return $this->exchangeCryptos;
    }

    public function addExchangeCrypto(TokenCrypto $tokenCrypto): self
    {
        if (!$this->exchangeCryptos->contains($tokenCrypto)) {
            $this->exchangeCryptos->add($tokenCrypto);
        }

        return $this;
    }

    public function containsExchangeCrypto(Crypto $crypto): bool
    {
        /** @var TokenCrypto $tokenCrypto */
        foreach ($this->exchangeCryptos->toArray() as $tokenCrypto) {
            if ($tokenCrypto->getCrypto()->getId() === $crypto->getId()) {
                return true;
            }
        }

        return false;
    }

    public function getId(): int
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

    public function setUpdatingMintDestination(): self
    {
        $this->mintDestination = '0x';

        return $this;
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

    public function getTwitterUrl(): ?string
    {
        return $this->twitterUrl;
    }

    public function setTwitterUrl(?string $twitterUrl): self
    {
        $this->twitterUrl = $twitterUrl;

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
     * @Groups({"API", "dev", "API_BASIC"})
     */
    public function getDeploymentStatus(): string
    {
        $mainDeploy = $this->getMainDeploy();

        if (!$mainDeploy) {
            return self::NOT_DEPLOYED;
        }

        if ($mainDeploy->isPending()) {
            return self::PENDING;
        }

        return self::DEPLOYED;
    }

    public function getMainDeploy(): ?TokenDeploy
    {
        return $this->getDeploys()[0] ?? null;
    }

    public function getLastDeploy(): ?TokenDeploy
    {
        $deploys = $this->getDeploys();

        return $deploys
            ? end($deploys)
            : null;
    }

    public function isDeployed(): bool
    {
        return self::DEPLOYED === $this->getDeploymentStatus();
    }

    /**
     * @return TokenDeploy[]
     */
    public function getDeploys(): array
    {
        return $this->deploys->toArray();
    }

    public function addDeploy(TokenDeploy $deploy): self
    {
        if (!$this->deploys->contains($deploy)) {
            $this->deploys->add($deploy);
        }

        return $this;
    }

    public function removeDeploy(TokenDeploy $deploy): self
    {
        if (!$this->deploys->contains($deploy)) {
            $this->deploys->removeElement($deploy);
        }

        return $this;
    }

    public function getDeployByCrypto(Crypto $crypto): ?TokenDeploy
    {
        foreach ($this->getDeploys() as $deploy) {
            if ($crypto->getId() === $deploy->getCrypto()->getId()) {
                return $deploy;
            }
        }

        return null;
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

    /** @Groups({"Default", "API", "API_BASIC"}) */
    public function getTelegramUrl(): ?string
    {
        return $this->telegramUrl;
    }

    public function setTelegramUrl(?string $url): self
    {
        $this->telegramUrl = $url;

        return $this;
    }

    /** @Groups({"Default", "API", "API_BASIC"}) */
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
    public function getWithdrawn(): Money
    {
        return new Money($this->withdrawn, new Currency(Symbols::TOK));
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

    public function getImage(): Image
    {
        $url = Image::TOKEN_AVATARS_PATH .'/' . strtoupper($this->name[0]) . '.png';

        return $this->image ?? Image::defaultImage($url);
    }

    public function setCoverImage(Image $image): void
    {
        $this->coverImage = $image;
    }

    public function getCoverImage(): ?Image
    {
        return $this->coverImage;
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
    /** @Groups({"EXTENDED_INFO"}) */
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

    /** @Groups({"Default", "API", "dev"}) */
    public function isQuiet(): bool
    {
        return $this->isQuiet;
    }

    public function setIsQuiet(bool $isQuiet): self
    {
        $this->isQuiet = $isQuiet;

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

    /**
     * @return TokenSignupHistory[]
     */
    public function getTokenSignupHistory(): array
    {
        return $this->tokenSignupHistory->toArray();
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

    public function getNextReminderDate(): ?\DateTimeImmutable
    {
        return $this->nextReminderDate;
    }

    public function setNextReminderDate(\DateTimeImmutable $nextReminderDate): self
    {
        $this->nextReminderDate = $nextReminderDate;

        return $this;
    }

    public function addThread(Thread $thread): self
    {
        $this->threads->add($thread);

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

    public function getVotings(): array
    {
        return $this->votings->toArray();
    }

    public function getShowSubunit(): int
    {
        return min(self::TOKEN_SUBUNIT, $this->getDecimals()) ?? self::TOKEN_SUBUNIT;
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

    public function isCreatedOnMintmeSite(): bool
    {
        return $this->createdOnMintmeSite;
    }

    public function setCreatedOnMintmeSite(bool $createdOnMintmeSite): self
    {
        $this->createdOnMintmeSite = $createdOnMintmeSite;

        return $this;
    }

    public function getTokenProposalMinAmount(): string
    {
        return $this->tokenProposalMinAmount;
    }

    public function setTokenProposalMinAmount(string $tokenProposalMinAmount): self
    {
        $this->tokenProposalMinAmount = $tokenProposalMinAmount;

        return $this;
    }

    public function getDmMinAmount(): string
    {
        return $this->dmMinAmount;
    }

    public function setDmMinAmount(string $dmMinAmount): self
    {
        $this->dmMinAmount = $dmMinAmount;

        return $this;
    }

    public function getCommentMinAmount(): string
    {
        return $this->commentMinAmount;
    }

    public function setCommentMinAmount(string $commentMinAmount): self
    {
        $this->commentMinAmount = $commentMinAmount;

        return $this;
    }

    public function getRank(): ?int
    {
        return $this->rank && $this->isDeployed()
            ? $this->rank->getRank()
            : null;
    }

    public function getSignUpBonusCode(): ?TokenSignupBonusCode
    {
        return $this->signUpBonusCode;
    }

    public function setSignUpBonusCode(?TokenSignupBonusCode $signUpBonusCode): self
    {
        $this->signUpBonusCode = $signUpBonusCode;

        return $this;
    }

    /** @Groups({"Default", "API", "dev"}) */
    public function getHasTax(): bool
    {
        return $this->hasTax;
    }

    public function setHasTax(bool $hasTax): self
    {
        $this->hasTax = $hasTax;

        return $this;
    }

    /** @Groups({"Default", "API", "dev"}) */
    public function getIsPausable(): bool
    {
        return $this->isPausable;
    }

    public function setIsPausable(bool $isPausable): self
    {
        $this->isPausable = $isPausable;

        return $this;
    }

    /** @Groups({"Default", "API", "dev"}) */
    public function getDepositsDisabled(): bool
    {
        return $this->depositsDisabled;
    }

    public function setDepositsDisabled(bool $disabled): self
    {
        $this->depositsDisabled = $disabled;

        return $this;
    }

    /** @Groups({"Default", "API", "dev"}) */
    public function getWithdrawalsDisabled(): bool
    {
        return $this->withdrawalsDisabled;
    }

    public function setWithdrawalsDisabled(bool $disabled): self
    {
        $this->withdrawalsDisabled = $disabled;

        return $this;
    }

    /** @Groups({"Default", "API", "dev"}) */
    public function getTradesDisabled(): bool
    {
        return $this->tradesDisabled;
    }

    public function setTradesDisabled(bool $disabled): self
    {
        $this->tradesDisabled = $disabled;

        return $this;
    }

    /** @Groups({"Default", "API", "dev"}) */
    public function getPriceDecimals(): ?int
    {
        return $this->priceDecimals;
    }

    public function setPriceDecimals(?int $priceDecimals): self
    {
        $this->priceDecimals = $priceDecimals;

        return $this;
    }

    /**
     * @return DeployNotification[]
     */
    public function getDeployNotifications(): array
    {
        return $this->deployNotifications->toArray();
    }
}
