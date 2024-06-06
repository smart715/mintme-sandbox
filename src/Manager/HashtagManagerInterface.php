<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Hashtag;
use App\Exception\ApiBadRequestException;

interface HashtagManagerInterface
{
    /**
     * @return Hashtag[]
     * @throws ApiBadRequestException
     */
    public function findOrCreate(string $content): array;

    public function getPopularHashtags(): array;

    /**
     * @return Hashtag[]
     */
    public function findHashtagsByKeyword(string $query): array;

    /**
     * @throws ApiBadRequestException
     */
    public function normalizeHashtagValue(string $hashtag): string;
}
