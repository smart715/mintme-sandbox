<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\Token\Token;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\DeployNotificationRepository")
 * @ORM\Table(
 *      name="deploy_notification",
 *      uniqueConstraints={@ORM\UniqueConstraint(name="deploy_notification_index", columns={"user_id", "token_id"})}
 * )
 * @ORM\HasLifecycleCallbacks()
 * @codeCoverageIgnore
 */
class DeployNotification
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="user_id", onDelete="CASCADE")
     */
    protected User $notifier;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Token\Token")
     * @ORM\JoinColumn(name="token_id", onDelete="CASCADE")
     */
    protected Token $token;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=false, options={"default": "CURRENT_TIMESTAMP"})
     */
    private \DateTimeImmutable $createdAt;

    public function __construct(User $user, Token $token)
    {
        $this->notifier = $user;
        $this->token = $token;
    }

    public function getNotifier(): User
    {
        return $this->notifier;
    }

    public function getToken(): Token
    {
        return $this->token;
    }

    /** @ORM\PrePersist() */
    public function setCreatedAt(): self
    {
        $this->createdAt = new \DateTimeImmutable();

        return $this;
    }
}
