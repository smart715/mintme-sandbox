<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\Token\Token;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserNotificationRepository")
 * @ORM\Table(name="user_notifications")
 * @ORM\HasLifecycleCallbacks()
 * @codeCoverageIgnore
 */
class UserNotification
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @ORM\JoinColumn(nullable=false)
     * @var String
     */
    private $type;

    /** @ORM\Column(type="json", nullable=true) */
    private ?array $json_data;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     * @var User
     */
    protected $user;

    /**
     * @ORM\Column(type="boolean")
     * @var Bool
     */
    private $viewed;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @var DateTimeImmutable
     */
    private $date;

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

    public function getViewed(): ?bool
    {
        return $this->viewed;
    }

    public function setViewed(bool $viewed): self
    {
        $this->viewed = $viewed;

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

    /** @ORM\PrePersist() */
    public function onAdd(): void
    {
        $this->setDate(new DateTimeImmutable());
    }
}
