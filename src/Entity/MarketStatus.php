<?php declare(strict_types = 1);

namespace App\Entity;

use App\Exchange\MarketInfo;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MarketInfoRepository")
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
    protected $openPrice;

    /**
     * @ORM\Column(type="string")
     * @Groups({"API"})
     * @var string
     */
    protected $lastPrice;

    /**
     * @ORM\Column(type="string")
     * @Groups({"API"})
     * @var string
     */
    protected $dayVolume;

    public function __construct(Crypto $crypto, MarketInfo $marketInfo)
    {
        $this->crypto = $crypto;
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
}
