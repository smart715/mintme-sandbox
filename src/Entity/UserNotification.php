<?php declare(strict_types = 1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserNotificationRepository")
 * @ORM\Table(
 *     name="user_notifications",
 *     indexes={
 *         @ORM\Index(name="IDX_FK_unotifications_users", columns={"user_id"}),
 *         @ORM\Index(name="IDX_NOTIFICATION_DATE", columns={"date"})
 *
 *   }
 * )
 * @ORM\HasLifecycleCallbacks()
 * @codeCoverageIgnore
 */
class UserNotification implements NotificationInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @var int
     */
    protected int $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @ORM\JoinColumn(nullable=false)
     * @var String
     */
    private string $type;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private ?array $json_data;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     * @var User
     */
    protected User $user;

    private bool $viewed;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @var DateTimeImmutable
     */
    private DateTimeImmutable $date;

    /**
     * @Groups({"default", "API"})
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @Groups({"default", "API"})
     */
    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @Groups({"default", "API"})
     */
    public function getJsonData(): ?array
    {
        return $this->json_data;
    }

    public function setJsonData(array $json_data): self
    {
        $this->json_data = $json_data;

        return $this;
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

    /**
     * @Groups({"default", "API"})
     */
    public function getViewed(): bool
    {
        return $this->viewed;
    }

    public function setViewed(bool $viewed): self
    {
        $this->viewed = $viewed;

        return $this;
    }

    /**
     * @Groups({"default", "API"})
     */
    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(DateTimeImmutable $Date): self
    {
        $this->date = $Date;

        return $this;
    }

    /** @ORM\PrePersist() */
    public function onAdd(): void
    {
        $this->setDate(new DateTimeImmutable());
    }
}
