<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\Token\Token;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserNotificationChannelRepository")
 * @ORM\Table(name="user_notifications_channel")
 * @ORM\HasLifecycleCallbacks()
 * @codeCoverageIgnore
 */
class UserNotificationChannel
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @var int
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     * @var User
     */
    protected User $user;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     * @var String
     */
    private string $channel;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @var DateTimeImmutable
     */
    private DateTimeImmutable $created;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function getCreated(): DateTimeImmutable
    {
        return $this->created;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function setChannel(string $channel): self
    {
        $this->channel = $channel;

        return $this;
    }

    public function setCreated(DateTimeImmutable $created): self
    {
        $this->created = $created;

        return $this;
    }

    /** @ORM\PrePersist() */
    public function onAdd(): void
    {
        $this->setCreated(new DateTimeImmutable());
    }
}
