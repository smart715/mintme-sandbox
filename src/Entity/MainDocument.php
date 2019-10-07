<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\Media\Media;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Doctrine\ORM\EntityRepository")
 * @ORM\Table(name="main_documents")
 * @codeCoverageIgnore
 */
class MainDocument
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Media\Media")
     * @ORM\JoinColumn(name="media_id", referencedColumnName="id", nullable=true)
     * @var Media
     */
    private $document;

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

    /**
     * @return Media|string
     */
    public function getDocument()
    {
        return $this->document ?? '';
    }

    public function setDocument(?Crypto $document): void
    {
        $this->document = $document;
    }
}
