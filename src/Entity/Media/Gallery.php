<?php

namespace App\Entity\Media;

use Sonata\MediaBundle\Entity\BaseGallery;

class Gallery extends BaseGallery
{

    /** @var int $id */
    protected $id;

    /**
     * Get id
     *
     * @return int $id
     */
    public function getId(): ?int
    {
        return $this->id;
    }
}
