<?php

namespace App\Entity\Classification;

use Sonata\ClassificationBundle\Entity\BaseContext;

class Context extends BaseContext
{
    /** @var string $id */
    protected $id;

    /**
     * Get id
     *
     * @return string $id
     */
    public function getId(): ?string
    {
        return $this->id;
    }
}
