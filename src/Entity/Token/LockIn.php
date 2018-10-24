<?php

namespace App\Entity\Token;

use App\Validator\Constraints\GreaterThanPrevious;
use Doctrine\ORM\Mapping as ORM;
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
     * @ORM\Column(type="float")
     * @var float
     */
    protected $amountToRelease;

    /**
     * @ORM\Column(type="float")
     * @var float
     */
    protected $frozenAmount = 0;

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
    public function getHourlyRate(): float
    {
        return $this->amountToRelease / $this->releasePeriod / 365 / 24;
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getReleasedAmount(): float
    {
        return $this->amountToRelease - $this->frozenAmount;
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getFrozenAmount(): float
    {
        return $this->frozenAmount;
    }

    public function setReleasePeriod(int $releasePeriod): self
    {
        $this->releasePeriod = $releasePeriod;

        return $this;
    }

    public function setAmountToRelease(float $amount): self
    {
        $this->amountToRelease =  $amount;
        $this->frozenAmount = $amount;

        return $this;
    }

    public function updateFrozenAmount(): self
    {
        if ($this->getHourlyRate() > $this->getFrozenAmount()) {
            $this->frozenAmount = 0;
        } else {
            $this->frozenAmount -= $this->getHourlyRate();
        }

        return $this;
    }
}
