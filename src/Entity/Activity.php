<?php declare(strict_types = 1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @codeCoverageIgnore
 * @ORM\Entity()
 */
class Activity
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private int $id;

    /** @ORM\Column(type="integer") */
    private int $type;

    /** @ORM\Column(type="json") */
    private array $context;

    /** @ORM\Column(type="datetime_immutable", options={"default": "CURRENT_TIMESTAMP"}) */
    private DateTimeImmutable $createdAt;

    public function __construct(int $type, array $context)
    {
        $this->type = $type;
        $this->context = $context;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    /** @Groups({"Default", "API"}) */
    public function getType(): int
    {
        return $this->type;
    }

    /** @Groups({"Default", "API"}) */
    public function getContext(): array
    {
        return $this->context;
    }

    /** @Groups({"Default", "API"}) */
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function uniquenize(): void
    {
        $this->context['id'] = $this->id;
    }
}
