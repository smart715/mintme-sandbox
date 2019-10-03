<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\ApiKey;
use Doctrine\ORM\EntityRepository;

class ApiKeyRepository extends EntityRepository
{
    public function findApiKey(string $keyValue): ?ApiKey
    {
        /** @var ApiKey|null $key */
        $key = $this->findOneBy(['publicKey' => $keyValue]);

        return $key;
    }
}
