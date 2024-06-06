<?php declare(strict_types = 1);

namespace App\Entity\KnowledgeBase;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="knowledge_base")
 * @ORM\Entity(repositoryClass="Doctrine\ORM\EntityRepository")
 * @UniqueEntity(
 *     fields={"url"},
 *     message="This url is already use."
 * )
 * @codeCoverageIgnore
 */
class KnowledgeBase
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\KnowledgeBase\Category")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id")
     * @Assert\NotBlank
     * @var Category|null
     */
    protected $category;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\KnowledgeBase\Subcategory")
     * @ORM\JoinColumn(name="subcategory_id", referencedColumnName="id", nullable=true)
     * @var Subcategory|null
     */
    protected $subcategory;

    /**
     * @ORM\Column(type="string")
     */
    protected ?string $title;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $esTitle;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $arTitle;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $frTitle;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $plTitle;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $ptTitle;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $ruTitle;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $uaTitle;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $deTitle;

    /**
     * @ORM\Column(type="string", unique=true, nullable=false)
     */
    protected ?string $url;

    /**
     * @ORM\Column(type="text")
     */
    protected ?string $description;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected ?string $esDescription;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected ?string $arDescription;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected ?string $frDescription;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected ?string $plDescription;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected ?string $ptDescription;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected ?string $ruDescription;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected ?string $uaDescription;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected ?string $deDescription;

    /**
     * @Gedmo\SortablePosition
     * @ORM\Column(type="integer", options={"default": -1})
     */
    protected int $position = -1; //phpcs:ignore

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title ?? '';
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getUrl(): string
    {
        return $this->url ?? '';
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getDescription(): string
    {
        return $this->description ?? '';
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return Category|string|null
     */
    public function getCategory()
    {
        return $this->category;
    }

    public function setCategory(?Category $category): void
    {
        $this->category = $category;
    }

    /**
     * @return Subcategory|string|null
     */
    public function getSubcategory()
    {
        return $this->subcategory;
    }

    public function setSubcategory(?Subcategory $subcategory): void
    {
        $this->subcategory = $subcategory;
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

    public function getEsDescription(): string
    {
        return $this->esDescription ?? '';
    }

    public function setEsDescription(?string $esDescription): self
    {
        $this->esDescription = $esDescription;

        return $this;
    }

    public function getEsTitle(): string
    {
        return $this->esTitle ?? '';
    }

    public function setEsTitle(?string $esTitle): self
    {
        $this->esTitle = $esTitle;

        return $this;
    }

    public function getArTitle(): string
    {
        return $this->arTitle ?? '';
    }

    public function setArTitle(?string $arTitle): self
    {
        $this->arTitle = $arTitle;

        return $this;
    }

    public function getFrTitle(): string
    {
        return $this->frTitle ?? '';
    }

    public function setFrTitle(?string $frTitle): self
    {
        $this->frTitle = $frTitle;

        return $this;
    }

    public function getPlTitle(): string
    {
        return $this->plTitle ?? '';
    }

    public function setPlTitle(?string $plTitle): self
    {
        $this->plTitle = $plTitle;

        return $this;
    }

    public function getPtTitle(): string
    {
        return $this->ptTitle ?? '';
    }

    public function setPtTitle(?string $ptTitle): self
    {
        $this->ptTitle = $ptTitle;

        return $this;
    }

    public function getRuTitle(): string
    {
        return $this->ruTitle ?? '';
    }

    public function setRuTitle(?string $ruTitle): self
    {
        $this->ruTitle = $ruTitle;

        return $this;
    }

    public function getUaTitle(): string
    {
        return $this->uaTitle ?? '';
    }

    public function setUaTitle(?string $uaTitle): self
    {
        $this->uaTitle = $uaTitle;

        return $this;
    }

    public function getDeTitle(): string
    {
        return $this->deTitle ?? '';
    }

    public function setDeTitle(?string $deTitle): self
    {
        $this->deTitle = $deTitle;

        return $this;
    }

    public function getArDescription(): string
    {
        return $this->arDescription ?? '';
    }

    public function setArDescription(?string $arDescription): self
    {
        $this->arDescription = $arDescription;

        return $this;
    }

    public function getFrDescription(): string
    {
        return $this->frDescription ?? '';
    }

    public function setFrDescription(?string $frDescription): self
    {
        $this->frDescription = $frDescription;

        return $this;
    }

    public function getPlDescription(): string
    {
        return $this->plDescription ?? '';
    }

    public function setPlDescription(?string $plDescription): self
    {
        $this->plDescription = $plDescription;

        return $this;
    }

    public function getPtDescription(): string
    {
        return $this->ptDescription ?? '';
    }

    public function setPtDescription(?string $ptDescription): self
    {
        $this->ptDescription = $ptDescription;

        return $this;
    }

    public function getRuDescription(): string
    {
        return $this->ruDescription ?? '';
    }

    public function setRuDescription(?string $ruDescription): self
    {
        $this->ruDescription = $ruDescription;

        return $this;
    }

    public function getUaDescription(): string
    {
        return $this->uaDescription ?? '';
    }

    public function setUaDescription(?string $uaDescription): self
    {
        $this->uaDescription = $uaDescription;

        return $this;
    }

    public function getDeDescription(): string
    {
        return $this->deDescription ?? '';
    }

    public function setDeDescription(?string $deDescription): self
    {
        $this->deDescription = $deDescription;

        return $this;
    }
}
