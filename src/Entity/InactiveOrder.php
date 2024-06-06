<?php declare(strict_types = 1);

namespace App\Entity;

use App\Repository\InactiveOrderRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=InactiveOrderRepository::class)
 * @ORM\HasLifecycleCallbacks()
 * @codeCoverageIgnore
 */
class InactiveOrder
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="integer")
     */
    private int $orderId;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private User $user;

    /**
     * @ORM\ManyToOne(targetEntity=Crypto::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private Crypto $base;

    /**
     * @ORM\ManyToOne(targetEntity=Crypto::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private Crypto $quote;

    /** @ORM\Column(type="datetime_immutable") */
    protected \DateTimeImmutable $createdAt;

    public function __construct(User $user, Crypto $base, Crypto $quote, int $orderId)
    {
        $this->user = $user;
        $this->base = $base;
        $this->quote = $quote;
        $this->orderId = $orderId;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getBase(): Crypto
    {
        return $this->base;
    }

    public function getQuote(): Crypto
    {
        return $this->quote;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @ORM\PrePersist */
    public function setCreatedAt(): self
    {
        $this->createdAt = new \DateTimeImmutable();

        return $this;
    }
}
