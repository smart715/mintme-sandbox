<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\Token\Token;
use App\Exchange\MarketInfo;
use Doctrine\ORM\Mapping as ORM;
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
     * @Groups({"API"})
     * @var string
     */
    private $openPrice;

    /**
     * @ORM\Column(type="string")
     * @Groups({"API"})
     * @var string
     */
    private $lastPrice;

    /**
     * @ORM\Column(type="string")
     * @Groups({"API"})
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

    public function getOpenPrice(): string
    {
        return $this->openPrice;
    }

    public function setOpenPrice(string $openPrice): void
    {
        $this->openPrice = $openPrice;
    }

    public function getLastPrice(): string
    {
        return $this->lastPrice;
    }

    public function setLastPrice(string $lastPrice): void
    {
        $this->lastPrice = $lastPrice;
    }

    public function getDayVolume(): string
    {
        return $this->dayVolume;
    }

    public function setDayVolume(string $dayVolume): void
    {
        $this->dayVolume = $dayVolume;
    }
}
