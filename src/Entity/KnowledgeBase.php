<?php declare(strict_types = 1);


namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="knowledge_base")
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
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getShortUrl(): string
    {
        return $this->shortUrl;
    }

    public function setShortUrl(string $shortUrl): void
    {
        $this->shortUrl = $shortUrl;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }
}
