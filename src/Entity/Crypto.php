<?php declare(strict_types = 1);

namespace App\Entity;

use App\Utils\Symbols;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CryptoRepository")
 * @UniqueEntity("name", message="Crypto name is already added")
 * @UniqueEntity("symbol", message="Crypto symbol is already added")
 * @codeCoverageIgnore
 */
class Crypto implements TradableInterface, ImagineInterface
{
    public const TRADABLE_TYPE = 'crypto';
    public const BLOCKCHAIN_STATUS_OK = 'ok';
    public const BLOCKCHAIN_STATUS_FAILED = 'failed';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    protected int $id;

    /**
     * @ORM\Column(type="string", length=20, unique=true)
     */
    protected string $name;

    /**
     * @ORM\Column(type="string", length=20, unique=true)
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
     * @ORM\Column(type="integer")
     * @Groups({"Default", "API"})
     */
    protected int $nativeSubunit;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected ?string $fee;

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
     * blockchain native coin, Null if blockchain has its own native coin or if it's token
     *
     * @ORM\OneToOne(targetEntity="Crypto", fetch="EAGER")
     * @ORM\JoinColumn(name="native_coin_id", referencedColumnName="id")
     */
    protected ?Crypto $nativeCoin;

    /**
     * @ORM\Column(type="string", length=20, options={"default": Crypto::BLOCKCHAIN_STATUS_OK})
     */
    protected string $blockchainStatus = self::BLOCKCHAIN_STATUS_OK; // phpcs:ignore

    /**
     * @ORM\OneToMany(
     *     targetEntity="App\Entity\Voting\CryptoVoting",
     *     mappedBy="crypto"
     * )
     * @ORM\OrderBy({"createdAt" = "DESC", "endDate" = "DESC"})
     *  @var ArrayCollection
     */
    private $votings;

    /**
     * @ORM\OneToMany(targetEntity=WrappedCryptoToken::class, mappedBy="crypto", fetch="EXTRA_LAZY")
     */
    private Collection $wrappedCryptoTokens;

    /** @ORM\Column(type="float", options={"default": 0}) */
    private float $usdExchangeRate = 0; // phpcs:ignore

    public function __construct(
        string $name,
        string $symbol,
        int $subunit,
        int $nativeSubunit,
        int $showSubunit,
        bool $tradable,
        bool $exchangeble,
        bool $isToken,
        ?string $fee,
        ?Crypto $nativeCoin = null
    ) {
        $this->name = $name;
        $this->symbol = $symbol;
        $this->subunit = $subunit;
        $this->nativeSubunit = $nativeSubunit;
        $this->showSubunit = $showSubunit;
        $this->tradable = $tradable;
        $this->exchangeble = $exchangeble;
        $this->isToken = $isToken;
        $this->fee = $fee;
        $this->nativeCoin = $nativeCoin;

        $this->imagePath = self::getDefaultImageUrl($symbol);
        $this->votings = new ArrayCollection();
        $this->wrappedCryptoTokens = new ArrayCollection();
    }

    public static function getDefaultImageUrl(string $symbol, string $ext = 'svg'): string
    {
        return '/media/default_' . strtolower($symbol) . ".$ext";
    }

    public function getId(): int
    {
        return $this->id;
    }

    public static function getTradableType(): string
    {
        return self::TRADABLE_TYPE;
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

    /** @Groups({"DEFAULT", "API"}) */
    public function getMoneySymbol(): string
    {
        return $this->isAsset()
            ? $this->getSymbol()
            : $this->nativeCoin->getMoneySymbol();
    }

    public function setSymbol(string $symbol): self
    {
        $this->symbol = $symbol;

        return $this;
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

    /**
     * If Crypto is a token, fee must be taken from its WrappedCryptoToken
     */
    public function getFee(): ?Money
    {
        return $this->fee && !$this->isToken
            ? new Money($this->fee, new Currency($this->getMoneySymbol()))
            : null;
    }

    public function setFee(Money $money): self
    {
        $this->fee = $money->getAmount();

        return $this;
    }

    public function setImage(Image $image): void
    {
        throw new \Exception('Not supported by crypto');
    }

    /**
     * @Groups({"Default", "API"})
     */
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

    public function getVotings(): array
    {
        return $this->votings->toArray();
    }

    public function setSubunit(int $subunit): self
    {
        $this->subunit = $subunit;

        return $this;
    }

    public function setShowSubunit(int $showSubunit): self
    {
        $this->showSubunit = $showSubunit;

        return $this;
    }

    /**
     * @return WrappedCryptoToken[]
     */
    public function getWrappedCryptoTokens(?bool $includingDisabled = false): array
    {
        return $includingDisabled
            ? $this->wrappedCryptoTokens->toArray()
            : $this->wrappedCryptoTokens->filter(fn(WrappedCryptoToken $wct) => $wct->isEnabled())->toArray();
    }

    public function getWrappedTokenByCrypto(Crypto $crypto): ?WrappedCryptoToken
    {
        foreach ($this->getWrappedCryptoTokens() as $wCryptoToken) {
            if ($crypto->getId() === $wCryptoToken->getCryptoDeploy()->getId()) {
                return $wCryptoToken;
            }
        }

        return null;
    }

    public function canBeWithdrawnTo(Crypto $cryptoNetwork): bool
    {
        return $this->getId() === $cryptoNetwork->getId()
            || $this->getWrappedTokenByCrypto($cryptoNetwork);
    }

    public function getUsdExchangeRate(): float
    {
        return $this->usdExchangeRate;
    }

    public function setUsdExchangeRate(float $usdExchangeRate): void
    {
        $this->usdExchangeRate = $usdExchangeRate;
    }

    public function getBlockchainStatus(): string
    {
        return $this->blockchainStatus;
    }

    public function setBlockchainStatus(string $status): self
    {
        $this->blockchainStatus = $status;

        return $this;
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function isBlockchainAvailable(): bool
    {
        return self::BLOCKCHAIN_STATUS_OK === $this->getBlockchainStatus();
    }

    public function getNativeSubunit(): int
    {
        return $this->nativeSubunit;
    }

    public function setNativeSubunit(int $nativeSubunit): self
    {
        $this->nativeSubunit = $nativeSubunit;

        return $this;
    }

    public function getNativeCoin(): Crypto
    {
        return $this->nativeCoin ?? $this;
    }

    public function isNative(): bool
    {
        return null === $this->nativeCoin;
    }

    public function isAsset(): bool
    {
        return $this->isToken() || $this->isNative();
    }
}
