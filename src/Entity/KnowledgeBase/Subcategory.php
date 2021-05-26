<?php declare(strict_types = 1);

namespace App\Entity\KnowledgeBase;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="knowledge_base_subcategory")
 * @ORM\Entity(repositoryClass="Doctrine\ORM\EntityRepository")
 */
class Subcategory
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    protected $name;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $esName;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $arName;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $frName;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $plName;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $ptName;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $ruName;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $uaName;

    /**
     * @Gedmo\SortablePosition
     * @ORM\Column(type="integer")
     * @var int
     */
    private $position;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name ?? '';
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    public function getEsName(): string
    {
        return $this->esName ?? '';
    }

    public function setEsName(?string $esName): self
    {
        $this->esName = $esName;

        return $this;
    }

    public function getArName(): string
    {
        return $this->arName ?? '';
    }

    public function setArName(?string $arName): self
    {
        $this->arName = $arName;

        return $this;
    }

    public function getFrName(): string
    {
        return $this->frName ?? '';
    }

    public function setFrName(?string $frName): self
    {
        $this->frName = $frName;

        return $this;
    }

    public function getPlName(): string
    {
        return $this->plName ?? '';
    }

    public function setPlName(?string $plName): self
    {
        $this->plName = $plName;

        return $this;
    }

    public function getPtName(): string
    {
        return $this->ptName ?? '';
    }

    public function setPtName(?string $ptName): self
    {
        $this->ptName = $ptName;

        return $this;
    }

    public function getRuName(): string
    {
        return $this->ruName ?? '';
    }

    public function setRuName(?string $ruName): self
    {
        $this->ruName = $ruName;

        return $this;
    }

    public function getUaName(): string
    {
        return $this->uaName ?? '';
    }

    public function setUaName(?string $uaName): self
    {
        $this->uaName = $uaName;

        return $this;
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
