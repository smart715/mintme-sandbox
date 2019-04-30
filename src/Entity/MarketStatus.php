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
     * @ORM\Column(type="string")
     * @Groups({"API"})
     * @var string
     */
    private $tokenName;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $currency;

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

    public function __construct(Crypto $crypto, MarketInfo $marketInfo)
    {
        $this->crypto = $crypto;
        $this->tokenName = $marketInfo->getTokenName();
        $this->openPrice = $marketInfo->getOpen()->getAmount();
        $this->lastPrice = $marketInfo->getLast()->getAmount();
        $this->dayVolume = $marketInfo->getVolume()->getAmount();
        $this->currency = $marketInfo->getCurrency();
    }

    public function getCrypto(): Crypto
    {
        return $this->crypto;
    }

    public function setCrypto(Crypto $crypto): void
    {
        $this->crypto = $crypto;
    }

    public function getTokenName(): string
    {
        return $this->tokenName;
    }

    public function setTokenName(string $tokenName): void
    {
        $this->tokenName = $tokenName;
    }

    /**
     * @Groups({"API"})
     */
    public function getOpenPrice(): Money
    {
        return new Money($this->openPrice, new Currency(MoneyWrapper::TOK_SYMBOL));
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
        return new Money($this->lastPrice, new Currency(MoneyWrapper::TOK_SYMBOL));
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
        return new Money($this->dayVolume, new Currency(MoneyWrapper::TOK_SYMBOL));
    }

    public function setDayVolume(string $dayVolume): void
    {
        $this->dayVolume = $dayVolume;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    public function updateStats(MarketInfo $marketInfo): void
    {
        $this->openPrice = $marketInfo->getOpen()->getAmount();
        $this->lastPrice = $marketInfo->getLast()->getAmount();
        $this->dayVolume = $marketInfo->getVolume()->getAmount();
    }
}
