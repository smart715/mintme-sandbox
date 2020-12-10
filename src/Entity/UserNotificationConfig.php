<?php declare(strict_types = 1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserNotificationConfigRepository")
 * @ORM\Table(name="user_notifications_config")
 * @ORM\HasLifecycleCallbacks()
 * @codeCoverageIgnore
 */
class UserNotificationConfig
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
     * @ORM\Column(type="string", length=255, nullable=false)
     * @var String
     */
    private string $type;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @var DateTimeImmutable
     */
    private DateTimeImmutable $created;

    /**
     * @Groups({"default", "API"})
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @Groups({"default", "API"})
     */
    public function getChannel(): string
    {
        return $this->channel;
    }

    /**
     * @Groups({"default", "API"})
     */
    public function getType(): string
    {
        return $this->type;
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

    public function setType(string $type): self
    {
        $this->type = $type;

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
