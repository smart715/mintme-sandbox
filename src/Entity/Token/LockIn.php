<?php

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
     * @Assert\Regex(pattern="/^[1-8]0$/")
     * @GreaterThanPrevious(message="Release period can be prolonged only.")
     * @var int
     */
    protected $releasePeriod = 10;

    /**
     * @ORM\Column(type="bigint")
     * @var string
     */
    protected $amountToRelease;

    /**
     * @ORM\Column(type="bigint")
     * @var string
     */
    protected $frozenAmount = '0';

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Token\Token", inversedBy="lockIn")
     * @var Token
     */
    protected $token;

    public function __construct(Token $token)
    {
        $this->token = $token;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @Groups({"Default", "API"})
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
        $money = new Money($this->amountToRelease, new Currency(MoneyWrapper::TOK_SYMBOL));

        return $money->subtract($this->getFrozenAmount());
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getFrozenAmount(): Money
    {
        return new Money($this->frozenAmount, new Currency(MoneyWrapper::TOK_SYMBOL));
    }

    public function setReleasePeriod(int $releasePeriod): self
    {
        $this->releasePeriod = $releasePeriod;

        return $this;
    }

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
}