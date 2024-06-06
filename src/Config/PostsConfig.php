<?php declare(strict_types = 1);

namespace App\Config;

/** @codeCoverageIgnore  */
class PostsConfig
{
    private array $postCommentsTipConfig;

    public function __construct(array $postCommentsTipConfig)
    {
        $this->postCommentsTipConfig = $postCommentsTipConfig;
    }

    public function getCommentsTipCost(): float
    {
        return $this->postCommentsTipConfig['cost'];
    }

    public function getCommentsTipMinAmount(): float
    {
        return $this->postCommentsTipConfig['min_amount'];
    }

    public function getCommentsTipMaxAmount(): float
    {
        return $this->postCommentsTipConfig['max_amount'];
    }
}
