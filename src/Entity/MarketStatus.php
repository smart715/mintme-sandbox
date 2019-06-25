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

    /**
     * @codeCoverageIgnore
     * @Groups({"API"})
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
     * @Groups({"API"})
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

        return $this;
    }
}
