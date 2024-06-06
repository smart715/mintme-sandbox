<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\ApiKey;
use App\Repository\ApiKeyRepository;

class ApiKeyManager implements ApiKeyManagerInterface
{
    private ApiKeyRepository $apiKeyRepository;

    public function __construct(ApiKeyRepository $apiKeyRepository)
    {
        $this->apiKeyRepository = $apiKeyRepository;
    }

    public function findApiKey(string $keyValue): ?ApiKey
    {
        return $this->apiKeyRepository->findApiKey($keyValue);
    }
}
