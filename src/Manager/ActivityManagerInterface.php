<?php declare(strict_types = 1);

namespace App\Manager;

interface ActivityManagerInterface
{
    public function getLast(int $limit): array;
}
