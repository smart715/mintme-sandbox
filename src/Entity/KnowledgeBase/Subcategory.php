<?php declare(strict_types = 1);

namespace App\Entity\KnowledgeBase;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

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
     * @ORM\ManyToOne(targetEntity="App\Entity\KnowledgeBase\Category")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id")
     * @Assert\NotBlank()
     * @var Category
     */
    protected $category;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    protected $name;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return Category|string
     */
    public function getCategory()
    {
        return $this->category ?? '';
    }

    public function setCategory(Category $category): void
    {
        $this->category = $category;
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
}
