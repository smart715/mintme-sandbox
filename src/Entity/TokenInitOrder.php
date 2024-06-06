<?php declare(strict_types = 1);

namespace App\Entity;

use App\Repository\TokenInitOrderRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TokenInitOrderRepository::class)
 * @ORM\Table(
 *     name="token_init_orders",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="token_init_orders_index", columns={"user_id", "order_id"})}
 * )
 * @ORM\HasLifecycleCallbacks()
 * @codeCoverageIgnore
 */
class TokenInitOrder
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="tokenInitOrders")
     * @ORM\JoinColumn(nullable=false)
     */
    private User $user;

    /**
     * @ORM\Column(type="integer", name="order_id")
     */
    private int $orderId;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    private string $marketName;

    public function getId(): int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function setOrderId(int $orderId): self
    {
        $this->orderId = $orderId;

        return $this;
    }

    public function getMarketName(): string
    {
        return $this->marketName;
    }

    public function setMarketName(string $marketName): self
    {
        $this->marketName = $marketName;

        return $this;
    }
}
