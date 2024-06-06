<?php declare(strict_types = 1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * @ORM\Entity(repositoryClass="App\Repository\BlockedUserRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(
 *     name="blocked_users",
 *     uniqueConstraints={@UniqueConstraint(name="blocked_user_index", columns={"user_id", "blocked_user_id"})}
 *     )
 * @codeCoverageIgnore
 */
class BlockedUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=false)
     */
    protected User $user;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected User $blockedUser;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=false)
     */
    protected DateTimeImmutable $created;

    public function __construct(User $user, User $blockedUser)
    {
        $this->user = $user;
        $this->blockedUser = $blockedUser;
    }

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

    public function getBlockedUser(): User
    {
        return $this->blockedUser;
    }

    public function setBlockedUser(User $blockedUser): self
    {
        $this->blockedUser = $blockedUser;

        return $this;
    }

    public function getCreated(): DateTimeImmutable
    {
        return $this->created;
    }

    /** @ORM\PrePersist() */
    public function setCreatedValue(): self
    {
        $this->created = new DateTimeImmutable();

        return $this;
    }
}
