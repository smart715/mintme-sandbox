<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\ApiKey;

interface ApiKeyManagerInterface
{
    public function findApiKey(string $keyValue): ?ApiKey;
}
