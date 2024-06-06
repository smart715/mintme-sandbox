<?php declare(strict_types = 1);

namespace App\Entity\Token;

use App\Entity\Crypto;
use App\Entity\PromotionHistory;
use App\Entity\User;
use App\Wallet\Model\Status;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TokenReleaseAddressHistoryRepository")
 * @ORM\HasLifecycleCallbacks()
 * @codeCoverageIgnore
 */
class TokenReleaseAddressHistory extends PromotionHistory
{
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Token\Token")
     * @ORM\JoinColumn(nullable=false)
     */
    private Token $token;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Crypto")
     * @ORM\JoinColumn(nullable=false)
     */
    private Crypto $crypto;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    private string $cost;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $oldAddress;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    private string $newAddress;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    private string $status;

    public function __construct(
        User $user,
        Token $token,
        Crypto $crypto,
        Money $cost,
        ?string $oldAddress,
        string $newAddress
    ) {
        $this->user = $user;
        $this->token = $token;
        $this->crypto = $crypto;
        $this->cost = $cost->getAmount();
        $this->oldAddress = $oldAddress;
        $this->newAddress = $newAddress;

        $this->setPendingStatus();
    }

    public function setToken(Token $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getToken(): Token
    {
        return $this->token;
    }

    public function setCrypto(Crypto $crypto): self
    {
        $this->crypto = $crypto;

        return $this;
    }

    /**
     * @Groups({"Default", "API", "PROMOTION_HISTORY"})
     */
    public function getCrypto(): Crypto
    {
        return $this->crypto;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function setCost(Money $cost): self
    {
        $this->cost = $cost->getAmount();

        return $this;
    }

    public function getCost(): Money
    {
        return new Money($this->cost, new Currency($this->crypto->getMoneySymbol()));
    }

    public function getAmount(): Money
    {
        return $this->getCost();
    }

    public function setOldAddress(?string $oldAddress): self
    {
        $this->oldAddress = $oldAddress;

        return $this;
    }

    public function getOldAddress(): ?string
    {
        return $this->oldAddress;
    }

    public function setNewAddress(string $newAddress): self
    {
        $this->newAddress = $newAddress;

        return $this;
    }

    public function getNewAddress(): string
    {
        return $this->newAddress;
    }

    /**
     * @throws \RuntimeException
     */
    private function setStatus(string $status): self
    {
        if (!in_array($status, [Status::PENDING, Status::ERROR, Status::PAID])) {
            throw new \RuntimeException('Invalid status for TokenReleaseAddressHistory');
        }

        $this->status = $status;

        return $this;
    }

    public function setPendingStatus(): self
    {
        return $this->setStatus(Status::PENDING);
    }

    public function setErrorStatus(): self
    {
        return $this->setStatus(Status::ERROR);
    }

    public function setPaidStatus(): self
    {
        return $this->setStatus(Status::PAID);
    }

    /**
     * @Groups({"Default", "API", "PROMOTION_HISTORY"})
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    public function getType(): string
    {
        return self::TOKEN_RELEASE_ADDRESS;
    }

    /**
     * @ORM\PrePersist()
     */
    public function setCreatedAt(): self
    {
        $this->createdAt = new \DateTimeImmutable();

        return $this;
    }
}
