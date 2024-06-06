<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\Token\Token;
use App\Exchange\MarketInfo;
use App\Utils\Symbols;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money;
use Swagger\Annotations as SWG;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MarketStatusRepository")
 * @ORM\Table(name="market_status")
 * @ORM\HasLifecycleCallbacks()
 */
class MarketStatus
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Crypto")
     * @ORM\JoinColumn(name="crypto_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     * @SWG\Property(ref="#/definitions/Currency", property="base")
     * @var Crypto
     */
    private $crypto;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Token\Token")
     * @ORM\JoinColumn(name="quote_token_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     * @var Token|null
     */
    private $quoteToken;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Crypto")
     * @ORM\JoinColumn(name="quote_crypto_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     * @var Crypto|null
     */
    private $quoteCrypto;

    /**
     * @ORM\Column(type="decimal", precision=65)
     * @SWG\Property(type="number")
     * @var string
     */
    private $openPrice = '0'; // phpcs:ignore

    /**
     * @ORM\Column(type="decimal", precision=65)
     * @SWG\Property(type="number")
     * @var string
     */
    private $lastPrice = '0'; // phpcs:ignore

    /**
     * @ORM\Column(type="decimal", precision=16, scale=2, options={"default": "0"})
     * @var string
     */
    private $changePercentage = '0'; // phpcs:ignore

    /**
     * @ORM\Column(type="decimal", precision=65, options={"default": "0"})
     * @var string
     */
    private $marketCap = '0'; // phpcs:ignore

    /**
     * @ORM\Column(type="decimal", precision=65)
     * @SWG\Property(type="number")
     * @var string
     */
    private $dayVolume = '0'; // phpcs:ignore

    /**
     * @ORM\Column(type="decimal", precision=65)
     * @var string
     */
    private $monthVolume = '0'; // phpcs:ignore;

    /**
     * @ORM\Column(type="decimal", precision=65, options={"default": "0"})
     * @var string
     */
    private $volumeDonation = '0'; // phpcs:ignore;

    /**
     * @ORM\Column(type="decimal", precision=65)
     * @SWG\Property(type="number")
     * @var string
     */
    private $buyDepth = '0';

    /**
     * @ORM\Column(type="decimal", precision=65, options={"default": "0"})
     * @var string
     */
    private $soldOnMarket = '0'; // phpcs:ignore;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     * @var \DateTimeImmutable|null
     */
    private $expires = null;

    /**
     * @ORM\Column(type="integer", options={"default": 0})
     */
    private int $lastDealId = 0; // phpcs:ignore

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $holders = null; // phpcs:ignore

    /**
     * @ORM\Column(type="simple_array", nullable=true)
     */
    private ?array $networks = null; // phpcs:ignore

    public function __construct(Crypto $crypto, TradableInterface $quote, ?MarketInfo $marketInfo = null)
    {
        $this->crypto = $crypto;
        $this->setQuote($quote);

        if ($marketInfo) {
            $this->updateStats($marketInfo);
        }
    }
    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function preUpdate(): void
    {
        $this->updateChangePercentage();
        $this->updateMarketCap();
    }

    /**
     * @codeCoverageIgnore
     * @SerializedName("base")
     * @Groups({"API", "dev"})
     */
    public function getCrypto(): Crypto
    {
        return $this->crypto;
    }

    /** @codeCoverageIgnore */
    public function setCrypto(Crypto $crypto): self
    {
        $this->crypto = $crypto;

        return $this;
    }

    /**
     * @Groups({"API", "dev"})
     */
    public function getOpenPrice(): Money
    {
        return new Money($this->openPrice, new Currency($this->crypto->getSymbol()));
    }

    /**
     * @Groups({"API", "dev"})
     */
    public function getLastPrice(): Money
    {
        return new Money($this->lastPrice, new Currency($this->crypto->getSymbol()));
    }

    public function updateMarketCap(): self
    {
        $lastPrice = BigDecimal::of($this->lastPrice);
        $quoteDecimals = $this->quoteCrypto
            ? $this->quoteCrypto->getSubunit()
            : $this->quoteToken->getDecimals();
        $soldOnMarket = BigDecimal::of($this->soldOnMarket)->dividedBy(
            10 ** $quoteDecimals,
            $quoteDecimals,
            RoundingMode::HALF_UP
        );

        $this->marketCap = (string)$lastPrice->multipliedBy($soldOnMarket);

        return $this;
    }

    public function updateChangePercentage(): self
    {
        $lastPrice = BigDecimal::of($this->lastPrice);
        $openPrice = BigDecimal::of($this->openPrice);

        if (!$openPrice->isZero()) {
            $this->changePercentage = (string)$lastPrice
                ->minus($openPrice)
                ->dividedBy($openPrice, 2, RoundingMode::HALF_UP);
        }

        return $this;
    }

    /**
     * @Groups({"API", "dev"})
     */
    public function getChangePercentage(): string
    {
        return $this->changePercentage;
    }

    /**
     * @Groups({"API", "dev"})
     */
    public function getMarketCap(): Money
    {
        return new Money($this->marketCap, new Currency($this->crypto->getSymbol()));
    }

    /**
     * @Groups({"API", "dev"})
     */
    public function getDayVolume(): Money
    {
        return new Money($this->dayVolume, new Currency($this->crypto->getSymbol()));
    }

    /**
     * @Groups({"API"})
     */
    public function getMonthVolume(): Money
    {
        return new Money($this->monthVolume, new Currency($this->crypto->getSymbol()));
    }

    public function getVolumeDonation(): Money
    {
        return new Money($this->volumeDonation, new Currency($this->getQuote()->getMoneySymbol()));
    }

    public function setQuote(?TradableInterface $quote): self
    {
        if ($quote instanceof Crypto) {
            $this->quoteCrypto = $quote;
            $this->quoteToken = null;
        } elseif ($quote instanceof Token) {
            $this->quoteToken = $quote;
            $this->quoteCrypto = null;
        }

        return $this;
    }

    /**
     * @Groups({"API", "dev"})
     */
    public function getQuote(): ?TradableInterface
    {
        return $this->quoteCrypto ?? $this->quoteToken;
    }

    public function updateStats(MarketInfo $marketInfo): self
    {
        $this->openPrice = $marketInfo->getOpen()->getAmount();
        $this->lastPrice = $marketInfo->getLast()->getAmount();
        $this->dayVolume = $marketInfo->getDeal()->getAmount();
        $this->monthVolume = $marketInfo->getMonthDeal()->getAmount();
        $this->buyDepth = $marketInfo->getBuyDepth()->getAmount();
        $this->soldOnMarket = $marketInfo->getSoldOnMarket()->getAmount();
        $this->volumeDonation = $marketInfo->getVolumeDonation()->getAmount();
        $this->expires = $marketInfo->getExpires();

        $this->updateChangePercentage();
        $this->updateMarketCap();

        return $this;
    }

    /**
     * @Groups({"API", "dev"})
     */
    public function getBuyDepth(): Money
    {
        return new Money($this->buyDepth, new Currency($this->crypto->getSymbol()));
    }

    public function getSoldOnMarket(): Money
    {
        return new Money(
            $this->soldOnMarket,
            new Currency($this->quoteCrypto ? $this->quoteCrypto->getSymbol() : Symbols::TOK)
        );
    }

    public function getExpires(): ?\DateTimeImmutable
    {
        return $this->expires;
    }

    public function setLastDealId(int $lastDealId): self
    {
        $this->lastDealId = $lastDealId;

        return $this;
    }

    public function getLastDealId(): int
    {
        return $this->lastDealId;
    }

    /** @Groups({"API"}) */
    public function getRank(): ?int
    {
        return $this->quoteToken
            ? $this->quoteToken->getRank()
            : null;
    }

    /** @Groups({"API"}) */
    public function setHolders(?int $holders): self
    {
        $this->holders = $holders;

        return $this;
    }

    /** @Groups({"API"}) */
    public function getHolders(): ?int
    {
        return $this->holders;
    }

    public function setNetworks(?array $networks): self
    {
        $this->networks = $networks;

        return $this;
    }

    /** @Groups({"API"}) */
    public function getNetworks(): ?array
    {
        return $this->networks;
    }

    public function getId(): int
    {
        return $this->id;
    }
}
