<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Crypto;
use Doctrine\ORM\EntityRepository;

class CryptoRepository extends EntityRepository
{
    /** @codeCoverageIgnore */
    public function getBySymbol(string $symbol): ?Crypto
    {
        return $this->findOneBy(['symbol' => $symbol]);
    }
}
