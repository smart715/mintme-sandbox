<?php declare(strict_types = 1);

namespace App\Entity\Classification;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Sonata\ClassificationBundle\Entity\BaseContext;

/**
 * @ORM\Entity(repositoryClass="Doctrine\ORM\EntityRepository")
 * @ORM\Table(name="classification__context")
 * @Serializer\ExclusionPolicy("all")
 * @Serializer\XmlRoot(name="_context")
 */
class Context extends BaseContext
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Groups({"sonata_api_read","sonata_api_write","sonata_search"})
     * @Serializer\Since(version="1.0")
     * @Serializer\Type(name="integer")
     * @Serializer\SerializedName("id")
     * @Serializer\XmlAttributeMap
     * @Serializer\Expose
     * @var int|null
     */
    protected $id;

    public function getId(): ?int
    {
        return $this->id;
    }
}
