<?php declare(strict_types = 1);

namespace App\Entity\KnowledgeBase;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="knowledge_base_category")
 * @ORM\Entity(repositoryClass="Doctrine\ORM\EntityRepository")
 */
class Category
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(type="string", nullable=false)
     * @var string
     */
    protected $name;

    /**
     * @Gedmo\SortablePosition
     * @ORM\Column(type="integer")
     * @var int
     */
    private $position;

    public function getName(): string
    {
        return $this->name ?? '';
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }
}
