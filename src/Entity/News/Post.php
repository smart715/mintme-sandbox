<?php

namespace App\Entity\News;

use Sonata\NewsBundle\Entity\BasePost;

class Post extends BasePost
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
