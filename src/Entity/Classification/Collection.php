<?php

namespace App\Entity\Classification;

use Sonata\ClassificationBundle\Entity\BaseCollection;

class Collection extends BaseCollection
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
