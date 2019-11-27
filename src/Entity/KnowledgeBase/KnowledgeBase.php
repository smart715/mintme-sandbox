<?php declare(strict_types = 1);

namespace App\Entity\KnowledgeBase;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="knowledge_base")
 * @ORM\Entity(repositoryClass="Doctrine\ORM\EntityRepository")
 * @UniqueEntity(
 *     fields={"url"},
 *     message="This url is already use."
 * )
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
     * @var string
     */
    protected $title;

    /**
     * @ORM\Column(type="string", unique=true, nullable=false)
     * @var string
     */
    protected $url;

    /**
     * @ORM\Column(type="text")
     * @var string
     */
    protected $description;

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
}
