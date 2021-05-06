<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\Token\Token;
use App\Exchange\MarketInfo;
use App\Utils\Symbols;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money;
use Swagger\Annotations as SWG;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MarketStatusRepository")
 * @ORM\Table(name="market_status")
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
     * @SWG\Property(ref="#/definitions/Сurrency", property="base")
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
     * @ORM\JoinColumn(name="quote_crypto_id", referencedColumnName="id", nullable=true)
     * @var Crypto|null
     */
    private $quoteCrypto;

    /**
     * @ORM\Column(type="string")
     * @SWG\Property(type="number")
     * @var string
     */
    private $openPrice;

    /**
     * @ORM\Column(type="string")
     * @SWG\Property(type="number")
     * @var string
     */
    private $lastPrice;

    /**
     * @ORM\Column(type="string")
     * @SWG\Property(type="number")
     * @var string
     */
    private $dayVolume;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $monthVolume;

    /**
     * @ORM\Column(type="string")
     * @SWG\Property(type="number")
     * @var string
     */
    private $buyDepth = '0';

    /**
     * @ORM\Column(type="string", options={"default": "0"})
     * @var string
     */
    private $soldOnMarket;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @var \DateTimeImmutable|null
     */
    private $expires = null;

    /**
     * @ORM\Column(type="integer", options={"default": 0})
     */
    private int $lastDealId = 0; // phpcs:ignore

    private ?int $rank = null; // phpcs:ignore

    public function __construct(Crypto $crypto, TradebleInterface $quote, MarketInfo $marketInfo)
    {
        $this->crypto = $crypto;
        $this->setQuote($quote);
        $this->openPrice = $marketInfo->getOpen()->getAmount();
        $this->lastPrice = $marketInfo->getLast()->getAmount();
        $this->dayVolume = $marketInfo->getDeal()->getAmount();
        $this->monthVolume = $marketInfo->getMonthDeal()->getAmount();
        $this->buyDepth = $marketInfo->getBuyDepth()->getAmount();
        $this->soldOnMarket = $marketInfo->getSoldOnMarket()->getAmount();
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

    public function setQuote(?TradebleInterface $quote): self
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
    public function getQuote(): ?TradebleInterface
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
        $this->expires = $marketInfo->getExpires();
        $this->soldOnMarket = $marketInfo->getSoldOnMarket()->getAmount();
        $this->expires = $marketInfo->getExpires();

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
        return $this->rank;
    }

    public function setRank(int $rank): void
    {
        $this->rank = $rank;
    }

    /** @Groups({"API"}) */
    public function getHolders(): int
    {
        return $this->quoteToken
            ? $this->quoteToken->getHoldersCount()
            : 0;
    }

    public function getId(): int
    {
        return $this->id;
    }
}
