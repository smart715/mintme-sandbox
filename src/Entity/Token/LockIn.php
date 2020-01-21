<?php declare(strict_types = 1);

namespace App\Entity\Token;

use App\Validator\Constraints\GreaterThanPrevious;
use App\Wallet\Money\MoneyWrapper;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/** @ORM\Entity(repositoryClass="App\Repository\LockInRepository") */
class LockIn
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int|null
     */
    protected $id;

    /**
     * @ORM\Column(type="integer")
     * @Assert\Regex(pattern="/^([0-3]|5|15|[0-5]0)$/")
     * @GreaterThanPrevious(message="Release period can be prolonged only.", groups={"Exchanged"})
     * @var int
     */
    protected $releasePeriod = 1;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    protected $amountToRelease = '0';

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    protected $frozenAmount = '0';

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    protected $releasedAtStart = '0';

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Token\Token", inversedBy="lockIn")
     * @var Token
     */
    protected $token;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @var \DateTimeImmutable
     */
    private $deployed;

    public function __construct(Token $token)
    {
        $this->token = $token;
    }

    /** @codeCoverageIgnore */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @Groups({"Default", "API"})
     * @codeCoverageIgnore
     */
    public function getReleasePeriod(): int
    {
        return $this->releasePeriod;
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getHourlyRate(): Money
    {
        $money = new Money($this->amountToRelease, new Currency(MoneyWrapper::TOK_SYMBOL));

        return 0 === $this->releasePeriod
            ? $money
            : $money->divide($this->releasePeriod)->divide(365 * 24);
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getReleasedAmount(): Money
    {
        $releasedAtStart = new Money($this->releasedAtStart, new Currency(MoneyWrapper::TOK_SYMBOL));

        return $this->token->isTokenDeployed()
            ? $releasedAtStart->add($this->getEarnedMoneyFromDeploy())
            : $releasedAtStart;
    }

    /**
     * @Groups({"Default", "API"})
     * @codeCoverageIgnore
     */
    public function getFrozenAmount(): Money
    {
        $notReleasedAtStart = new Money($this->amountToRelease, new Currency(MoneyWrapper::TOK_SYMBOL));

        if ($this->token->isTokenDeployed()) {
            $frozenAmount = $notReleasedAtStart->subtract($this->getEarnedMoneyFromDeploy());
            $greaterThan = new Money(0, new Currency(MoneyWrapper::TOK_SYMBOL));

            return $frozenAmount->greaterThan($greaterThan)
                ? $frozenAmount
                : $greaterThan;
        } else {
            return $notReleasedAtStart;
        }
    }

    /** @codeCoverageIgnore */
    public function getReleasedAtStart(): string
    {
        return $this->releasedAtStart;
    }

    /** @codeCoverageIgnore */
    public function setReleasedAtStart(string $releasedAtStart): self
    {
        $this->releasedAtStart = $releasedAtStart;

        return $this;
    }

    /** @codeCoverageIgnore */
    public function setReleasePeriod(int $releasePeriod): self
    {
        $this->releasePeriod = $releasePeriod;

        return $this;
    }

    /** @codeCoverageIgnore */
    public function setAmountToRelease(Money $amount): self
    {
        $this->amountToRelease =  $amount->getAmount();
        $this->frozenAmount = $amount->getAmount();

        return $this;
    }

    public function updateFrozenAmount(): self
    {
        $this->frozenAmount = $this->getHourlyRate()->greaterThan($this->getFrozenAmount())
            ? '0'
            : $this->getFrozenAmount()->subtract($this->getHourlyRate())->getAmount();

        return $this;
    }

    /** @codeCoverageIgnore */
    public function getDeployed(): ?\DateTimeImmutable
    {
        return $this->deployed;
    }

    /** @codeCoverageIgnore */
    public function setDeployed(\DateTimeImmutable $deployed): self
    {
        $this->deployed = $deployed;

        return $this;
    }

    /**
     * Get amount of hours that passed since token was deployed to blockchain.
     *
     * @return float
     */
    public function getCountHoursFromDeploy(): float
    {
        if ($this->deployed instanceof \DateTimeImmutable) {
            $timezone = date_default_timezone_get();
            date_default_timezone_set('UTC');
            $deployedTimestamp = strtotime($this->deployed->format('Y-m-d H:i:s'));
            $currentTimestamp = strtotime('now');
            $timestampDiff = abs($currentTimestamp - $deployedTimestamp);
            date_default_timezone_set($timezone);

            return round(($timestampDiff / 3600), 2);
        }

        return floatval(0);
    }

    public function getEarnedMoneyFromDeploy(): Money
    {
        return $this->getHourlyRate()->multiply($this->getCountHoursFromDeploy());
    }
}
