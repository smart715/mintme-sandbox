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

    public function __construct(Crypto $crypto, TradebleInterface $quote, MarketInfo $marketInfo)
    {
        $this->crypto = $crypto;
        $this->setQuote($quote);
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
            $this->quoteCrypto ? $this->quoteCrypto->getSymbol() : MoneyWrapper::TOK_SYMBOL
        ));
    }

    /**
     * @Groups({"API"})
     */
    public function getLastPrice(): Money
    {
        return new Money($this->lastPrice, new Currency(
            $this->quoteCrypto ? $this->quoteCrypto->getSymbol() : MoneyWrapper::TOK_SYMBOL
        ));
    }

    /**
     * @Groups({"API"})
     */
    public function getDayVolume(): Money
    {
        return new Money($this->dayVolume, new Currency(
            $this->quoteCrypto ? $this->quoteCrypto->getSymbol() : MoneyWrapper::TOK_SYMBOL
        ));
    }

    public function setQuote(?TradebleInterface $quote): void
    {
        if ($quote instanceof Crypto) {
            $this->quoteCrypto = $quote;
        } elseif ($quote instanceof Token) {
            $this->quoteToken = $quote;
        }
    }

    public function getQuote(): ?TradebleInterface
    {
        return $this->quoteCrypto ?? $this->quoteToken;
    }

    public function getQuoteToken(): ?Token
    {
        return $this->quoteToken;
    }

    public function getQuoteCrypto(): ?Crypto
    {
        return $this->quoteCrypto;
    }

    public function updateStats(MarketInfo $marketInfo): void
    {
        $this->openPrice = $marketInfo->getOpen()->getAmount();
        $this->lastPrice = $marketInfo->getLast()->getAmount();
        $this->dayVolume = $marketInfo->getVolume()->getAmount();
    }
}
