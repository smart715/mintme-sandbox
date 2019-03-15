<?php

namespace App\Exchange\Config;

class Config
{
    /** @var int $offset */
    private $offset;

    public function __construct(int $offset)
    {
        $this->offset = $offset;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }
}