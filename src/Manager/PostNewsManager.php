<?php declare(strict_types = 1);

namespace App\Manager;

use App\Repository\News\PostRepository;

class PostNewsManager implements PostNewsManagerInterface
{
    private PostRepository $postRepository;

    public function __construct(
        PostRepository $postRepository
    ) {
        $this->postRepository = $postRepository;
    }

    public function getRandomPostNews(int $limit): array
    {
        return $this->postRepository->getRandomPost($limit);
    }
}
