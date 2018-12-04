<?php

namespace App\Entity\News;

use Sonata\NewsBundle\Entity\BaseComment;

class Comment extends BaseComment
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
