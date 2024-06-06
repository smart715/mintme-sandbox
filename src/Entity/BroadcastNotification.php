<?php declare(strict_types = 1);

namespace App\Entity;

use App\Repository\BroadcastNotificationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @codeCoverageIgnore
 * @ORM\Entity(repositoryClass=BroadcastNotificationRepository::class)
 */
class BroadcastNotification implements NotificationInterface
{
    public const TYPE = 'broadcast';

    private bool $viewed;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /** @ORM\Column(type="text") */
    private string $enContent;

    /** @ORM\Column(type="text", nullable=true) */
    private string $esContent;

    /** @ORM\Column(type="text", nullable=true) */
    private string $frContent;

    /** @ORM\Column(type="text", nullable=true) */
    private string $deContent;

    /** @ORM\Column(type="datetime_immutable", options={"default": "CURRENT_TIMESTAMP"}) */
    private \DateTimeImmutable $date;

    public function __construct(string $enContent, ?string $esContent, ?string $frContent, ?string $deContent)
    {
        $this->enContent = $enContent;
        $this->esContent = $esContent;
        $this->frContent = $frContent;
        $this->deContent = $deContent;
        $this->viewed = false;
        $this->date = new \DateTimeImmutable();
    }

    /**
     * @Groups({"default", "API"})
     */
    public function getEnContent(): ?string
    {
        return $this->enContent;
    }

    /**
     * @Groups({"default", "API"})
     */
    public function getEsContent(): ?string
    {
        return $this->esContent;
    }

    /**
     * @Groups({"default", "API"})
     */
    public function getFrContent(): ?string
    {
        return $this->frContent;
    }

    /**
     * @Groups({"default", "API"})
     */
    public function getDeContent(): ?string
    {
        return $this->deContent;
    }

    /**
     * @Groups({"default", "API"})
     */
    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    /**
     * @Groups({"default", "API"})
     */
    public function getType(): string
    {
        return self::TYPE;
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
}
