<?php

namespace App\Entity\Classification;

use Sonata\ClassificationBundle\Entity\BaseCategory;

class Category extends BaseCategory
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
