<?php

namespace App\Entity\Classification;

use Sonata\ClassificationBundle\Entity\BaseTag;

class Tag extends BaseTag
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
