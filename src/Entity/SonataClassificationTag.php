<?php declare(strict_types = 1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Sonata\ClassificationBundle\Entity\BaseTag;

/**
 * @ORM\Entity
 * @ORM\Table(name="classification__tag")
 * @ORM\HasLifecycleCallbacks
 */
class SonataClassificationTag extends BaseTag
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * // Serializer\Groups(groups={"sonata_api_read", "sonata_api_write", "sonata_search"})
     */
    protected $id;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="App\Entity\SonataClassificationContext",
     *     cascade={"persist"}
     * )
     * @ORM\JoinColumn(name="context", referencedColumnName="id", nullable=false)
     */

     /** @var SonataClassificationContext */
    protected $context;

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist(): void
    {
        parent::prePersist();
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate(): void
    {
        parent::preUpdate();
    }
}
