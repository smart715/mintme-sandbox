<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\ApiKey;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/** @codeCoverageIgnore */
class ApiKeyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiKey::class);
    }

    public function findApiKey(string $keyValue): ?ApiKey
    {
        /** @var ApiKey|null $key */
        $key = $this->findOneBy(['publicKey' => $keyValue]);

        return $key;
    }
}
