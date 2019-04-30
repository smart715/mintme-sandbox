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
     * @Groups({"API"})
     * @var Token
     */
    private $token;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $currency = 'TOK';

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

    public function __construct(Crypto $crypto, Token $token, MarketInfo $marketInfo)
    {
        $this->crypto = $crypto;
        $this->token = $token;
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

    public function getToken(): Token
    {
        return $this->token;
    }

    public function setToken(Token $token): void
    {
        $this->token = $token;
    }

    /**
     * @Groups({"API"})
     */
    public function getOpenPrice(): Money
    {
        return new Money($this->openPrice, new Currency($this->getCurrency()));
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
        return new Money($this->lastPrice, new Currency($this->getCurrency()));
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
        return new Money($this->dayVolume, new Currency($this->getCurrency()));
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
