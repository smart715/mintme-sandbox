<?php declare(strict_types = 1);

namespace App\Entity\KnowledgeBase;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * @ORM\Table(name="knowledge_base")
 * @ORM\Entity(repositoryClass="Doctrine\ORM\EntityRepository")
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
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="category_id", referencedColumnName="id")
     *  })
     * @Assert\NotBlank
     * @var Category
     */
    protected $category;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    protected $title;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    protected $shortUrl;

    /**
     * @ORM\Column(type="string")
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

    public function getShortUrl(): string
    {
        return $this->shortUrl ?? '';
    }

    public function setShortUrl(string $shortUrl): void
    {
        $this->shortUrl = $shortUrl;
    }

    public function getDescription(): string
    {
        return $this->description ?? '';
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }
    
    public function setCategory(Category $category): void
    {
        $this->category = $category;
    }
}
