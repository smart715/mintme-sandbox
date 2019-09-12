<?php declare(strict_types = 1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="reciprocal_links")
 * @ORM\Entity(repositoryClass="Doctrine\ORM\EntityRepository")
 */
class ReciprocalLinks
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
    protected $title;

    /**
     * @ORM\Column(type="string", nullable=false)
     * @var string
     */
    protected $url;


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
}
