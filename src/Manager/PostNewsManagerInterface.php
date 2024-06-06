<?php declare(strict_types = 1);

namespace App\Manager;

use App\Repository\News\PostNewsRepository;

interface PostNewsManagerInterface
{
    public function getRandomPostNews(int $limit): array;
}
