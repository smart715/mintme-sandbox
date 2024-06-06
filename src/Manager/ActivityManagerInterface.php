<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Activity;

interface ActivityManagerInterface
{
    /**
     * @return Activity[]
     */
    public function getLast(int $limit): array;

    public function getLastByTypes(array $types, int $limit): array;
}
