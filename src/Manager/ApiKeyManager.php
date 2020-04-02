<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\ApiKey;
use App\Repository\ApiKeyRepository;
use Doctrine\ORM\EntityManagerInterface;

class ApiKeyManager implements ApiKeyManagerInterface
{
    /** @var ApiKeyRepository */
    private $repository;

    /** @var EntityManagerInterface */
    private $ormAdapter;

    public function __construct(EntityManagerInterface $ormAdapter)
    {
        $this->ormAdapter = $ormAdapter;
        $this->repository = $this->ormAdapter->getRepository(ApiKey::class);
    }

    public function findApiKey(string $keyValue): ?ApiKey
    {
        return $this->repository->findApiKey($keyValue);
    }
}
