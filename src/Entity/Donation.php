<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\Token\Token;
use App\Utils\Symbols;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money;

/**
 * @ORM\Entity(repositoryClass="App\Repository\DonationRepository")
 * @ORM\Table(name="donation")
 * @ORM\HasLifecycleCallbacks()
 * @codeCoverageIgnore
 */
class Donation
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="donor_id", referencedColumnName="id")
     * @var User
     */
    private $donor;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="token_creator_id", referencedColumnName="id")
     * @var User
     */
    private $tokenCreator;

    /**
     * @ORM\Column(type="string", length=6)
     * @var string
     */
    private $currency;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $amount;

    /**
     * @ORM\Column(name="fee_amount", type="string")
     * @var string
     */
    private $feeAmount;

    /**
     * @ORM\Column(name="token_amount", type="string")
     * @var string
     */
    private $tokenAmount = '0';

    /**
     * @ORM\Column(name="created_at", type="datetime_immutable")
     * @var \DateTimeImmutable
     */
    private $createdAt;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Token\Token")
     * @var Token|null
     */
    private $token;

    public function getId(): int
    {
        return $this->id;
    }

    public function getDonor(): User
    {
        return $this->donor;
    }

    public function setDonor(User $donor): self
    {
        $this->donor = $donor;

        return $this;
    }

    public function getTokenCreator(): User
    {
        return $this->tokenCreator;
    }

    public function setTokenCreator(User $tokenCreator): self
    {
        $this->tokenCreator = $tokenCreator;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getAmount(): Money
    {
        return new Money($this->amount, new Currency($this->currency));
    }

    public function setAmount(Money $amount): self
    {
        $this->amount = $amount->getAmount();

        return $this;
    }

    public function getFeeAmount(): Money
    {
        return new Money($this->feeAmount, new Currency($this->currency));
    }

    public function setFeeAmount(Money $feeAmount): self
    {
        $this->feeAmount = $feeAmount->getAmount();

        return $this;
    }

    public function getTokenAmount(): Money
    {
        return new Money($this->tokenAmount, new Currency(Symbols::WEB));
    }

    public function setTokenAmount(Money $tokenAmount): self
    {
        $this->tokenAmount = $tokenAmount->getAmount();

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @ORM\PrePersist() */
    public function setCreatedAt(): self
    {
        $this->createdAt = new \DateTimeImmutable();

        return $this;
    }

    public function setToken(Token $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getToken(): ?Token
    {
        return $this->token;
    }
}
