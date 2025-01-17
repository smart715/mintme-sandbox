<?php declare(strict_types = 1);

namespace App\Entity;

use App\Entity\Media\Media;
use App\Validator\Constraints\MainDocument as MainDocs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

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
     * @ORM\JoinColumn(name="media_id", referencedColumnName="id")
     * @Assert\NotBlank
     * @MainDocs()
     */
    private ?Media $document = null; // phpcs:ignore

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
        return $this->name;
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

    public function setDocument(Media $document): void
    {
        $this->document = $document;
    }
}
