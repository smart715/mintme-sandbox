<?php declare(strict_types = 1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ScheduledNotificationRepository")
 * @ORM\Table(name="scheduled_notifications")
 * @ORM\HasLifecycleCallbacks()
 * @codeCoverageIgnore
 */
class ScheduledNotification
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    protected int $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @ORM\JoinColumn(nullable=false)
     */
    private string $type;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     */
    protected User $user;

    /**
     * @ORM\Column(type="string", length=255)
     * @ORM\JoinColumn(nullable=false)
     */
    private string $timeInterval;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private DateTimeImmutable $date;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private DateTimeImmutable $dateToBeSend;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

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

    public function getTimeInterval(): string
    {
        return $this->timeInterval;
    }

    public function setTimeInterval(string $timeInterval): self
    {
        $this->timeInterval = $timeInterval;

        return $this;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(DateTimeImmutable $Date): self
    {
        $this->date = $Date;

        return $this;
    }

    public function setDateToBeSend(DateTimeImmutable $DateToBeSend): self
    {
        $this->dateToBeSend = $DateToBeSend;

        return $this;
    }

    public function getDateToBeSend(): DateTimeImmutable
    {
        return $this->dateToBeSend;
    }

    /** @ORM\PrePersist() */
    public function onAdd(): void
    {
        $this->setDate(new DateTimeImmutable());
    }
}
