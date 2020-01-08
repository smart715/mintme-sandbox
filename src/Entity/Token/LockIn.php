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
     * @Assert\Regex(pattern="/^([1-3]|5|15|[1-5]0)$/")
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

        return $money->divide($this->releasePeriod)->divide(365 * 24);
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getReleasedAmount(): Money
    {
        $releasedAtStart = new Money($this->releasedAtStart, new Currency(MoneyWrapper::TOK_SYMBOL));

        return $releasedAtStart->add($this->getEarnedMoneyFromDeploy());
    }

    /**
     * @Groups({"Default", "API"})
     * @codeCoverageIgnore
     * @return Money|string
     */
    public function getFrozenAmount()
    {
        $notReleasedAtStart = new Money($this->amountToRelease, new Currency(MoneyWrapper::TOK_SYMBOL));
        $frozenAmount = $notReleasedAtStart->subtract($this->getEarnedMoneyFromDeploy());
        $greaterThan = new Money(0, new Currency(MoneyWrapper::TOK_SYMBOL));

        return $frozenAmount->greaterThan($greaterThan)
            ? $frozenAmount
            : '0';
    }

    /** @codeCoverageIgnore */
    public function setReleasedAtStart(int $releasedAtStart): self
    {
        $this->releasedAtStart = (string)$releasedAtStart;

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

    public function getDeployed(): ?\DateTimeImmutable
    {
        return $this->deployed;
    }

    /** @ORM\PrePersist() */
    public function setDeployedValue(): self
    {
        $this->deployed = new \DateTimeImmutable();

        return $this;
    }

    /**
     * Get amount of hours that passed since token was deployed to blockchain.
     *
     * @return float
     */
    public function getCountHoursFromDeploy(): float
    {
        return round((strtotime('now') - strtotime($this->deployed->format('Y-m-d H:i:s'))) / 3600, 1);
    }

    public function getEarnedMoneyFromDeploy(): Money
    {
        return $this->getHourlyRate()->multiply($this->getCountHoursFromDeploy());
    }
}
