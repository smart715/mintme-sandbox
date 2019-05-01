<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\Token\Token;
use App\Exchange\MarketInfo;
use App\Wallet\Money\MoneyWrapper;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MarketStatusRepository")
 * @ORM\Table(name="market_status")
 * @ORM\HasLifecycleCallbacks()
 * @codeCoverageIgnore
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
     * @Groups({"API"})
     * @var Crypto
     */
    private $crypto;


    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Token\Token")
     * @ORM\JoinColumn(name="quote_token_id", referencedColumnName="id", nullable=true)
     * @Groups({"API"})
     * @var Token|null
     */
    private $quoteToken;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Crypto")
     * @ORM\JoinColumn(name="quote_crypto_id", referencedColumnName="id", nullable=true)
     * @Groups({"API"})
     * @var Crypto|null
     */
    private $quoteCrypto;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $openPrice;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $lastPrice;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $dayVolume;

    public function __construct(Crypto $crypto, ?Token $quoteToken, ?Crypto $quoteCrypto, MarketInfo $marketInfo)
    {
        $this->crypto = $crypto;
        $this->quoteToken = $quoteToken;
        $this->quoteCrypto = $quoteCrypto;
        $this->openPrice = $marketInfo->getOpen()->getAmount();
        $this->lastPrice = $marketInfo->getLast()->getAmount();
        $this->dayVolume = $marketInfo->getVolume()->getAmount();
    }

    public function getCrypto(): Crypto
    {
        return $this->crypto;
    }

    public function setCrypto(Crypto $crypto): void
    {
        $this->crypto = $crypto;
    }

    /**
     * @Groups({"API"})
     */
    public function getOpenPrice(): Money
    {
        return new Money($this->openPrice, new Currency(
            $this->getQuoteCrypto() ? $this->getQuoteCrypto()->getSymbol() : MoneyWrapper::TOK_SYMBOL
        ));
    }


    public function setOpenPrice(string $openPrice): void
    {
        $this->openPrice = $openPrice;
    }

    /**
     * @Groups({"API"})
     */
    public function getLastPrice(): Money
    {
        return new Money($this->lastPrice, new Currency(
            $this->getQuoteCrypto() ? $this->getQuoteCrypto()->getSymbol() : MoneyWrapper::TOK_SYMBOL
        ));
    }

    public function setLastPrice(string $lastPrice): void
    {
        $this->lastPrice = $lastPrice;
    }

    /**
     * @Groups({"API"})
     */
    public function getDayVolume(): Money
    {
        return new Money($this->dayVolume, new Currency(
            $this->getQuoteCrypto() ? $this->getQuoteCrypto()->getSymbol() : MoneyWrapper::TOK_SYMBOL
        ));
    }

    public function setDayVolume(string $dayVolume): void
    {
        $this->dayVolume = $dayVolume;
    }

    public function getQuoteToken(): ?Token
    {
        return $this->quoteToken;
    }

    public function setQuoteToken(?Token $quoteToken): void
    {
        $this->quoteToken = $quoteToken;
    }

    public function getQuoteCrypto(): ?Crypto
    {
        return $this->quoteCrypto;
    }

    public function setQuoteCrypto(?Crypto $quoteCrypto): void
    {
        $this->quoteCrypto = $quoteCrypto;
    }

    public function updateStats(MarketInfo $marketInfo): void
    {
        $this->openPrice = $marketInfo->getOpen()->getAmount();
        $this->lastPrice = $marketInfo->getLast()->getAmount();
        $this->dayVolume = $marketInfo->getVolume()->getAmount();
    }
}
