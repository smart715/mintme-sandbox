<?php

namespace App\Entity\Media;

use Sonata\MediaBundle\Entity\BaseGalleryHasMedia;

class GalleryHasMedia extends BaseGalleryHasMedia
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
